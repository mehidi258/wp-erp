<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Get all pay_purchases
 *
 * @param $data
 * @return mixed
 */
function erp_acct_get_pay_purchases( $args = [] ) {
    global $wpdb;

    $defaults = [
        'number'     => 20,
        'offset'     => 0,
        'orderby'    => 'id',
        'order'      => 'DESC',
        'count'      => false,
        's'          => '',
    ];

    $args = wp_parse_args( $args, $defaults );

    $limit = '';

    if ( $args['number'] != '-1' ) {
        $limit = "LIMIT {$args['number']} OFFSET {$args['offset']}";
    }

    $sql = "SELECT";
    $sql .= $args['count'] ? " COUNT( id ) as total_number " : " * ";
    $sql .= "FROM {$wpdb->prefix}erp_acct_pay_purchase ORDER BY {$args['orderby']} {$args['order']} {$limit}";

    if ( $args['count'] ) {
        return $wpdb->get_var($sql);
    }

    return $wpdb->get_results( $sql, ARRAY_A );
}

/**
 * Get a pay_purchase
 *
 * @param $purchase_no
 * @return mixed
 */
function erp_acct_get_pay_purchase( $purchase_no ) {
    global $wpdb;

    $sql = $wpdb->prepare("SELECT
        pay_purchase.voucher_no,
        pay_purchase.vendor_id,
        pay_purchase.vendor_name,
        pay_purchase.trn_date,
        pay_purchase.amount,
        pay_purchase.trn_by,
        pay_purchase.attachments,
        pay_purchase.trn_by_ledger_id
        FROM wp_erp_acct_pay_purchase AS pay_purchase
        WHERE pay_purchase.voucher_no = %d", $purchase_no);

    $row = $wpdb->get_row( $sql, ARRAY_A );

    $row['purchase_details'] = erp_acct_format_pay_purchase_line_items( $purchase_no );

    return $row;
}

/**
 * Format pay purchase line items
 */
function erp_acct_format_pay_purchase_line_items($voucher_no) {
    global $wpdb;

    $sql = $wpdb->prepare("SELECT * FROM
        wp_erp_acct_pay_purchase AS pay_purchase
        LEFT JOIN wp_erp_acct_pay_purchase_details AS pay_purchase_detail
        ON pay_purchase.voucher_no = pay_purchase_detail.voucher_no
        WHERE pay_purchase.voucher_no = %d", $voucher_no);

    return $wpdb->get_results($sql, ARRAY_A);
}

/**
 * Insert a pay_purchase
 *
 * @param $data
 * @param $pay_purchase_id
 * @param $due
 * @return mixed
 */
function erp_acct_insert_pay_purchase( $data ) {
    global $wpdb;

    $created_by = get_current_user_id();
    $data['created_at'] = date("Y-m-d H:i:s");
    $data['created_by'] = $created_by;
    $data['updated_at'] = date("Y-m-d H:i:s");
    $data['updated_by'] = $created_by;
    $purchase_no = '';

    try {
        $wpdb->query( 'START TRANSACTION' );

        //create voucher
        $wpdb->insert( $wpdb->prefix . 'erp_acct_voucher_no', array(
            'type'       => 'pay_purchase',
            'created_at' => $data['created_at'],
            'created_by' => $data['created_by'],
            'updated_at' => isset( $data['updated_at'] ) ? $data['updated_at'] : date( "Y-m-d" ),
            'updated_by' => isset( $data['updated_by'] ) ? $data['updated_by'] : get_current_user_id()
        ) );

        $voucher_no = $wpdb->insert_id;
        $purchase_no = $voucher_no;

        $pay_purchase_data = erp_acct_get_formatted_pay_purchase_data( $data, $voucher_no );

        $wpdb->insert( $wpdb->prefix . 'erp_acct_pay_purchase', array(
            'voucher_no'      => $voucher_no,
            'vendor_id'       => $pay_purchase_data['vendor_id'],
            'vendor_name'     => $pay_purchase_data['vendor_name'],
            'trn_date'        => $pay_purchase_data['trn_date'],
            'amount'          => $pay_purchase_data['amount'],
            'trn_by'          => $pay_purchase_data['trn_by'],
            'particulars'     => $pay_purchase_data['particulars'],
            'attachments'     => $pay_purchase_data['attachments'],
            'status'          => $pay_purchase_data['status'],
            'created_at'      => $pay_purchase_data['created_at'],
            'created_by'      => $created_by,
            'updated_at'      => $pay_purchase_data['updated_at'],
            'updated_by'      => $pay_purchase_data['updated_by'],
        ) );

        $items = $pay_purchase_data['purchase_details'];

        foreach ( $items as $key => $item ) {
            $wpdb->insert( $wpdb->prefix . 'erp_acct_pay_purchase_details', array(
                'voucher_no'  => $voucher_no,
                'purchase_no' => $item['voucher_no'],
                'amount'      => $item['line_total'],
                'created_at'  => $pay_purchase_data['created_at'],
                'created_by'  => $created_by,
                'updated_at'  => $pay_purchase_data['updated_at'],
                'updated_by'  => $pay_purchase_data['updated_by'],
            ) );

            erp_acct_insert_pay_purchase_data_into_ledger( $pay_purchase_data, $item );
        }

        foreach ( $items as $key => $item ) {
            $wpdb->insert( $wpdb->prefix . 'erp_acct_purchase_account_details', array(
                'purchase_no' => $item['voucher_no'],
                'trn_no'      => $voucher_no,
                'particulars' => $pay_purchase_data['particulars'],
                'debit'       => $item['line_total'],
                'credit'      => 0,
                'created_at'  => $pay_purchase_data['created_at'],
                'created_by'  => $created_by,
                'updated_at'  => $pay_purchase_data['updated_at'],
                'updated_by'  => $pay_purchase_data['updated_by'],
            ) );
        }

        erp_acct_insert_people_trn_data( $pay_purchase_data, $pay_purchase_data['vendor_id'], 'debit' );

        if ( isset( $pay_purchase_data['trn_by'] ) && $pay_purchase_data['trn_by'] === '3' ) {
            erp_acct_insert_check_data ( $pay_purchase_data );
        }

        $wpdb->query( 'COMMIT' );

    } catch (Exception $e) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_error( 'pay-purchase-exception', $e->getMessage() );
    }

    return $purchase_no;

}

/**
 * Update a pay_purchase
 *
 * @param $data
 * @param $pay_purchase_id
 * @param $due
 * @return mixed
 */
function erp_acct_update_pay_purchase( $data, $pay_purchase_id ) {
    global $wpdb;

    $created_by = get_current_user_id();

    try {
        $wpdb->query( 'START TRANSACTION' );

        $pay_purchase_data = erp_acct_get_formatted_pay_purchase_data( $data, $pay_purchase_id );

        $wpdb->update( $wpdb->prefix . 'erp_acct_pay_purchase', array(
            'trn_date'        => $pay_purchase_data['trn_date'],
            'amount'          => $pay_purchase_data['amount'],
            'trn_by'          => $pay_purchase_data['trn_by'],
            'particulars'     => $pay_purchase_data['particulars'],
            'attachments'     => $pay_purchase_data['attachments'],
            'created_at'      => $pay_purchase_data['created_at'],
            'created_by'      => $created_by,
            'updated_at'      => $pay_purchase_data['updated_at'],
            'updated_by'      => $pay_purchase_data['updated_by'],
        ), array (
            'voucher_no'      => $pay_purchase_id,
        ) );

        $items = $pay_purchase_data['purchase_details'];

        foreach ( $items as $key => $item ) {
            $wpdb->update( $wpdb->prefix . 'erp_acct_pay_purchase_details', array(
                'purchase_no' => $item['id'],
                'amount'      => $item['amount'],
                'created_at'  => $pay_purchase_data['created_at'],
                'created_by'  => $created_by,
                'updated_at'  => $pay_purchase_data['updated_at'],
                'updated_by'  => $pay_purchase_data['updated_by'],
            ), array (
                'voucher_no'  => $pay_purchase_id,
            ) );

            erp_acct_update_pay_purchase_data_into_ledger( $pay_purchase_data, $pay_purchase_id, $item );
        }

        foreach ( $items as $key => $item ) {
            $wpdb->update($wpdb->prefix . 'erp_acct_purchase_account_details', array(
                'trn_no'     => $pay_purchase_id,
                'particulars'=> $pay_purchase_data['particulars'],
                'debit'      => $item['line_total'],
                'credit'     => 0,
                'created_at' => $pay_purchase_data['created_at'],
                'created_by' => $created_by,
                'updated_at' => $pay_purchase_data['updated_at'],
                'updated_by' => $pay_purchase_data['updated_by'],
            ), array(
                'purchase_no' => $item['voucher_no'],
            ));
        }

        $wpdb->query( 'COMMIT' );

    } catch (Exception $e) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_error( 'pay-purchase-exception', $e->getMessage() );
    }

    return $pay_purchase_id;

}

/**
 * Delete a pay_purchase
 *
 * @param $id
 * @return void
 */
function erp_acct_delete_pay_purchase( $id ) {
    global $wpdb;

    if ( !$id ) {
        return;
    }

    $wpdb->delete( $wpdb->prefix . 'erp_acct_pay_purchase', array( 'voucher_no' => $id ) );
}

/**
 * Void a pay_purchase
 *
 * @param $id
 * @return void
 */
function erp_acct_void_pay_purchase( $id ) {
    global $wpdb;

    if ( !$id ) {
        return;
    }

    $wpdb->update($wpdb->prefix . 'erp_acct_pay_purchase',
        array(
            'status' => 'void',
        ),
        array( 'voucher_no' => $id )
    );
}

/**
 * Get formatted pay_purchase data
 *
 * @param $data
 * @param $voucher_no
 *
 * @return mixed
 */
function erp_acct_get_formatted_pay_purchase_data( $data, $voucher_no ) {
    $pay_purchase_data = [];

    $user_data = erp_get_people($data['vendor_id']);
    $company = new \WeDevs\ERP\Company();

    $pay_purchase_data['voucher_no']       = ! empty( $voucher_no ) ? $voucher_no : 0;
    $pay_purchase_data['order_no']         = isset( $data['order_no'] ) ? $data['order_no'] : 1;
    $pay_purchase_data['vendor_id']        = isset( $data['vendor_id'] ) ? $data['vendor_id'] : 1;
    $pay_purchase_data['vendor_name']      = $user_data->first_name. ' ' .$user_data->last_name;
    $pay_purchase_data['purchase_details'] = isset( $data['purchase_details'] ) ? $data['purchase_details'] : '';
    $pay_purchase_data['trn_date']         = isset( $data['trn_date'] ) ? $data['trn_date'] : date( "Y-m-d" );
    $pay_purchase_data['amount']           = isset( $data['amount'] ) ? $data['amount'] : 0;
    $pay_purchase_data['trn_by']           = isset( $data['trn_by'] ) ? $data['trn_by'] : '';
    $pay_purchase_data['attachments']      = isset( $data['attachments'] ) ? $data['attachments'] : '';
    $pay_purchase_data['ref']              = isset( $data['ref'] ) ? $data['ref'] : '';
    $pay_purchase_data['particulars']      = isset( $data['particulars'] ) ? $data['particulars'] : '';
    $pay_purchase_data['status']           = isset( $data['status'] ) ? $data['status'] : '';
    $pay_purchase_data['trn_by_ledger_id'] = isset( $data['deposit_to'] ) ? $data['deposit_to'] : null;
    $pay_purchase_data['check_no'] = isset( $data['check_no'] ) ? $data['check_no'] : 0;
    $pay_purchase_data['pay_to'] = isset( $user_data ) ?  $user_data->first_name . ' ' . $user_data->last_name : '';
    $pay_purchase_data['name'] = isset( $data['name'] ) ?  $data['name'] : $company->name;
    $pay_purchase_data['voucher_type'] = isset( $data['voucher_type'] ) ?  $data['voucher_type'] : '';
    $pay_purchase_data['created_at']       = date( "Y-m-d" );
    $pay_purchase_data['created_by']       = isset( $data['created_by'] ) ? $data['created_by'] : get_current_user_id();
    $pay_purchase_data['updated_at']       = isset( $data['updated_at'] ) ? $data['updated_at'] : date( "Y-m-d" );
    $pay_purchase_data['updated_by']       = isset( $data['updated_by'] ) ? $data['updated_by'] : get_current_user_id();

    return $pay_purchase_data;
}

/**
 * Insert pay_purchase/s data into ledger
 *
 * @param array $pay_purchase_data
 * @param array $item_data
 *
 * @return mixed
 */
function erp_acct_insert_pay_purchase_data_into_ledger( $pay_purchase_data, $item_data ) {
    global $wpdb;

    // Insert amount in ledger_details
    $wpdb->insert( $wpdb->prefix . 'erp_acct_ledger_details', array(
        'ledger_id'   => $pay_purchase_data['trn_by'],
        'trn_no'      => $pay_purchase_data['voucher_no'],
        'particulars' => $pay_purchase_data['particulars'],
        'debit'       => 0,
        'credit'      => $item_data['line_total'],
        'trn_date'    => $pay_purchase_data['trn_date'],
        'created_at'  => $pay_purchase_data['created_at'],
        'created_by'  => $pay_purchase_data['created_by'],
        'updated_at'  => $pay_purchase_data['updated_at'],
        'updated_by'  => $pay_purchase_data['updated_by'],
    ) );

}

/**
 * Update pay_purchase/s data into ledger
 *
 * @param array $pay_purchase_data
 * * @param array $pay_purchase_no
 * @param array $item_data
 *
 * @return mixed
 */
function erp_acct_update_pay_purchase_data_into_ledger( $pay_purchase_data, $pay_purchase_no, $item_data ) {
    global $wpdb;

    // Update amount in ledger_details
    $wpdb->update( $wpdb->prefix . 'erp_acct_ledger_details', array(
        'ledger_id'   => 605, // change later
        'particulars' => $pay_purchase_data['particulars'],
        'debit'       => $item_data['line_total'],
        'credit'      => 0,
        'trn_date'    => $pay_purchase_data['trn_date'],
        'created_at'  => $pay_purchase_data['created_at'],
        'created_by'  => $pay_purchase_data['created_by'],
        'updated_at'  => $pay_purchase_data['updated_at'],
        'updated_by'  => $pay_purchase_data['updated_by'],
    ), array(
        'trn_no' => $pay_purchase_no,
    ) );

}

/**
 * Get Pay purchases count
 *
 * @return int
 */
function erp_acct_get_pay_purchase_count() {
    global $wpdb;

    $row = $wpdb->get_row( "SELECT COUNT(*) as count FROM " . $wpdb->prefix . "erp_acct_pay_purchase" );

    return $row->count;
}
