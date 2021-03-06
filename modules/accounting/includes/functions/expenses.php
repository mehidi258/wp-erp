<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Get all expenses
 *
 * @param $data
 * @return mixed
 */
function erp_acct_get_expenses( $args = [] ) {
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
    $sql .= "FROM {$wpdb->prefix}erp_acct_expenses WHERE `trn_by_ledger_id` IS NOT NULL ORDER BY {$args['orderby']} {$args['order']} {$limit}";

    if ( $args['count'] ) {
        return $wpdb->get_var($sql);
    }

    $rows = $wpdb->get_results( $sql, ARRAY_A );

    return $rows;
}

/**
 * Get a single expense
 *
 * @param $expense_no
 * @return mixed
 */
function erp_acct_get_expense( $expense_no ) {
    global $wpdb;

    $sql = "SELECT

    expense.id,
    expense.voucher_no,
    expense.people_id,
    expense.people_name,
    expense.address,
    expense.trn_date,
    expense.amount,
    expense.ref,
    expense.particulars,
    expense.status,
    expense.trn_by_ledger_id,
    expense.trn_by,
    expense.attachments,
    expense.created_at,
    expense.created_by,
    expense.updated_at,
    expense.updated_by,

    b_detail.amount,

    ledg_detail.debit,
    ledg_detail.credit

    FROM {$wpdb->prefix}erp_acct_expenses AS expense

    LEFT JOIN {$wpdb->prefix}erp_acct_expense_details AS b_detail ON expense.voucher_no = b_detail.trn_no
    LEFT JOIN {$wpdb->prefix}erp_acct_ledger_details AS ledg_detail ON expense.voucher_no = ledg_detail.trn_no

    WHERE expense.voucher_no = {$expense_no}";

    $row = $wpdb->get_row( $sql, ARRAY_A );

    $row['bill_details'] = erp_acct_format_expense_line_items( $expense_no );

    return $row;
}

/**
 * Get a single check
 *
 * @param $expense_no
 * @return mixed
 */
function erp_acct_get_check( $expense_no ) {
    global $wpdb;

    $sql = "SELECT

    expense.id,
    expense.voucher_no,
    expense.people_id,
    expense.people_name,
    expense.address,
    expense.trn_date,
    expense.amount,
    expense.ref,
    expense.particulars,
    expense.status,
    expense.trn_by_ledger_id,
    expense.trn_by,
    expense.attachments,
    expense.created_at,
    expense.created_by,
    expense.updated_at,
    expense.updated_by,
    
    cheque.name,
    cheque.check_no,
    cheque.pay_to,
    cheque.amount,

    ledg_detail.debit,
    ledg_detail.credit

    FROM {$wpdb->prefix}erp_acct_expenses AS expense

    LEFT JOIN {$wpdb->prefix}erp_acct_expense_checks AS cheque ON expense.voucher_no = cheque.trn_no
    LEFT JOIN {$wpdb->prefix}erp_acct_ledger_details AS ledg_detail ON expense.voucher_no = ledg_detail.trn_no

    WHERE expense.voucher_no = {$expense_no} AND cheque.voucher_type = 'check'";

    $row = $wpdb->get_row( $sql, ARRAY_A );

    $row['bill_details'] = erp_acct_format_check_line_items( $expense_no );

    return $row;
}

/**
 * Format check line items
 */
function erp_acct_format_check_line_items( $voucher_no ) {
    global $wpdb;

    $sql = $wpdb->prepare("SELECT
        expense_detail.id,
        expense_detail.ledger_id,
        expense_detail.trn_no,
        expense_detail.particulars,
        
        cheque.name,
        cheque.check_no,
        cheque.pay_to,
        cheque.amount

        FROM {$wpdb->prefix}erp_acct_expenses AS expense
        LEFT JOIN {$wpdb->prefix}erp_acct_expense_details as expense_detail ON expense.voucher_no = expense_detail.trn_no
        LEFT JOIN {$wpdb->prefix}erp_acct_expense_checks AS cheque ON expense.voucher_no = cheque.trn_no

        WHERE expense.voucher_no = %d", $voucher_no);

    return $wpdb->get_results($sql, ARRAY_A);
}

/**
 * Format expense line items
 */
function erp_acct_format_expense_line_items( $voucher_no ) {
    global $wpdb;

    $sql = $wpdb->prepare("SELECT
        expense_detail.id,
        expense_detail.ledger_id,
        expense_detail.trn_no,
        expense_detail.particulars,
        expense_detail.amount

        FROM {$wpdb->prefix}erp_acct_expenses AS expense
        LEFT JOIN {$wpdb->prefix}erp_acct_expense_details as expense_detail ON expense.voucher_no = expense_detail.trn_no
        WHERE expense.voucher_no = %d", $voucher_no);

    return $wpdb->get_results($sql, ARRAY_A);
}

/**
 * Insert a expense
 *
 * @param $data
 * @return mixed
 */
function erp_acct_insert_expense( $data ) {
    global $wpdb;

    $created_by = get_current_user_id();
    $data['created_at'] = date("Y-m-d H:i:s");
    $data['created_by'] = $created_by;
    $data['updated_at'] = date("Y-m-d H:i:s");
    $data['updated_by'] = $created_by;
    try {
        $wpdb->query( 'START TRANSACTION' );

        $type = 'expense';
        if ( isset( $data['trn_by'] ) && $data['trn_by'] === '3' ) {
            $type = 'check';
        }

        $wpdb->insert( $wpdb->prefix . 'erp_acct_voucher_no', array(
            'type'       => $type,
            'created_at' => $data['created_at'],
            'created_by' => $data['created_by'],
            'updated_at' => isset( $data['updated_at'] ) ? $data['updated_at'] : '',
            'updated_by' => isset( $data['updated_by'] ) ? $data['updated_by'] : ''
        ) );

        $voucher_no = $wpdb->insert_id;

        $expense_data = erp_acct_get_formatted_expense_data( $data, $voucher_no );

        $wpdb->insert( $wpdb->prefix . 'erp_acct_expenses', array(
            'voucher_no'      => $expense_data['voucher_no'],
            'people_id'       => $expense_data['people_id'],
            'people_name'     => $expense_data['people_name'],
            'address'         => $expense_data['billing_address'],
            'trn_date'        => $expense_data['trn_date'],
            'amount'          => $expense_data['amount'],
            'ref'             => $expense_data['ref'],
            'check_no'        => $expense_data['check_no'],
            'particulars'     => $expense_data['particulars'],
            'status'          => $expense_data['status'],
            'trn_by'          => $expense_data['trn_by'],
            'trn_by_ledger_id'=> $expense_data['trn_by_ledger_id'],
            'attachments'     => $expense_data['attachments'],
            'created_at'      => $expense_data['created_at'],
            'created_by'      => $expense_data['created_by'],
            'updated_at'      => $expense_data['updated_at'],
            'updated_by'      => $expense_data['updated_by'],
        ) );

        $items = $expense_data['bill_details'];

        foreach ( $items as $key => $item ) {
            $wpdb->insert( $wpdb->prefix . 'erp_acct_expense_details', array(
                'trn_no'      => $voucher_no,
                'ledger_id'   => $item['ledger_id'],
                'particulars' => $item['description'],
                'amount'      => $item['amount'],
                'created_at'  => $expense_data['created_at'],
                'created_by'  => $expense_data['created_by'],
                'updated_at'  => $expense_data['updated_at'],
                'updated_by'  => $expense_data['updated_by'],
            ) );

            erp_acct_insert_expense_data_into_ledger( $expense_data, $item );
        }

        //Insert into Ledger for source account
        erp_acct_insert_source_expense_data_into_ledger( $expense_data );

        erp_acct_insert_people_trn_data( $expense_data, $expense_data['people_id'], 'debit' );

        if ( isset( $expense_data['trn_by'] ) && $expense_data['trn_by'] === '3' ) {
            erp_acct_insert_check_data ( $expense_data );
        }

        $wpdb->query( 'COMMIT' );

    } catch (Exception $e) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_error( 'expense-exception', $e->getMessage() );
    }

    return $voucher_no;

}

/**
 * Update a expense
 *
 * @param $data
 * @param $expense_id
 *
 * @return mixed
 */
function erp_acct_update_expense( $data, $expense_id ) {
    global $wpdb;

    $updated_by = get_current_user_id();
    $data['updated_at'] = date("Y-m-d H:i:s");
    $data['updated_by'] = $updated_by;

    try {
        $wpdb->query( 'START TRANSACTION' );

        $expense_data = erp_acct_get_formatted_expense_data( $data, $expense_id );

        $wpdb->update( $wpdb->prefix . 'erp_acct_expenses', array(
            'people_id'       => $expense_data['people_id'],
            'people_name'     => $expense_data['people_name'],
            'address'         => $expense_data['billing_address'],
            'trn_date'        => $expense_data['trn_date'],
            'amount'          => $expense_data['amount'],
            'ref'             => $expense_data['ref'],
            'check_no'        => $expense_data['check_no'],
            'particulars'     => $expense_data['particulars'],
            'status'          => $expense_data['status'],
            'trn_by'          => $expense_data['trn_by'],
            'trn_by_ledger_id'=> $expense_data['trn_by_ledger_id'],
            'attachments'     => $expense_data['attachments'],
            'created_at'      => $expense_data['created_at'],
            'created_by'      => $expense_data['created_by'],
            'updated_at'      => $expense_data['updated_at'],
            'updated_by'      => $expense_data['updated_by'],
        ), array(
            'voucher_no'      => $expense_id
        ) );

        $items = $expense_data['bill_details'];

        foreach ( $items as $key => $item ) {
            $wpdb->update( $wpdb->prefix . 'erp_acct_expense_details', array(
                'ledger_id'   => $item['ledger_id'],
                'particulars' => $item['remarks'],
                'amount'      => $item['amount'],
                'created_at'  => $expense_data['created_at'],
                'created_by'  => $expense_data['created_by'],
                'updated_at'  => $expense_data['updated_at'],
                'updated_by'  => $expense_data['updated_by'],
            ), array(
                'trn_no'  => $expense_id
            ));

            erp_acct_update_expense_data_into_ledger( $expense_data, $expense_id, $item );
        }


        $wpdb->query( 'COMMIT' );

    } catch (Exception $e) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_error( 'expense-exception', $e->getMessage() );
    }

    return $expense_id;

}

/**
 * Delete a expense
 *
 * @param $id
 * @return void
 */
function erp_acct_delete_expense( $id ) {
    global $wpdb;

    if ( !$id ) {
        return;
    }

    $wpdb->delete( $wpdb->prefix . 'erp_acct_expenses', array( 'voucher_no' => $id ) );
}

/**
 * Void a expense
 *
 * @param $id
 * @return void
 */
function erp_acct_void_expense( $id ) {
    global $wpdb;

    if ( !$id ) {
        return;
    }

    $wpdb->update($wpdb->prefix . 'erp_acct_expenses',
        array(
            'status' => 'void',
        ),
        array( 'voucher_no' => $id )
    );
}

/**
 * Get formatted expense data
 *
 * @param $data
 * @param $voucher_no
 *
 * @return mixed
 */
function erp_acct_get_formatted_expense_data( $data, $voucher_no ) {
    $expense_data = [];

    $people = erp_get_people( $data['people_id'] );
    $company = new \WeDevs\ERP\Company();

    $expense_data['voucher_no'] = !empty( $voucher_no ) ? $voucher_no : 0;
    $expense_data['people_id'] = isset( $data['people_id'] ) ? $data['people_id'] : get_current_user_id();
    $expense_data['people_name'] = isset( $people ) ?  $people->first_name . ' ' . $people->last_name : '';
    $expense_data['billing_address'] = isset( $data['billing_address'] ) ? $data['billing_address'] : '';
    $expense_data['trn_date']   = isset( $data['trn_date'] ) ? $data['trn_date'] : date("Y-m-d" );
    $expense_data['amount'] = isset( $data['amount'] ) ? $data['amount'] : 0;
    $expense_data['attachments'] = isset( $data['attachments'] ) ? $data['attachments'] : '';
    $expense_data['ref'] = isset( $data['ref'] ) ? $data['ref'] : '';
    $expense_data['check_no'] = isset( $data['check_no'] ) ? $data['check_no'] : 0;
    $expense_data['particulars'] = isset( $data['particulars'] ) ? $data['particulars'] : '';
    $expense_data['bill_details'] = isset( $data['bill_details'] ) ? $data['bill_details'] : '';
    $expense_data['status'] = isset( $data['status'] ) ? $data['status'] : 1;
    $expense_data['trn_by_ledger_id'] = isset( $data['trn_by_ledger_id'] ) ? $data['trn_by_ledger_id'] : null;
    $expense_data['trn_by'] = isset( $data['trn_by'] ) ? $data['trn_by'] : null;
    $expense_data['created_at'] = date("Y-m-d" );
    $expense_data['created_by'] = isset( $data['created_by'] ) ? $data['created_by'] : '';
    $expense_data['updated_at'] = isset( $data['updated_at'] ) ? $data['updated_at'] : '';
    $expense_data['updated_by'] = isset( $data['updated_by'] ) ? $data['updated_by'] : '';
    $expense_data['pay_to'] = isset( $people ) ?  $people->first_name . ' ' . $people->last_name : '';
    $expense_data['name'] = isset( $data['name'] ) ?  $data['name'] : $company->name;
    $expense_data['voucher_type'] = isset( $data['voucher_type'] ) ?  $data['voucher_type'] : '';

    return $expense_data;
}


/**
 * Insert expense/s data into ledger
 *
 * @param array $expense_data
 * @param array $item_data
 *
 * @return mixed
 */
function erp_acct_insert_expense_data_into_ledger( $expense_data, $item_data = [] ) {
    global $wpdb;

    // Insert amount in ledger_details
    $wpdb->insert( $wpdb->prefix . 'erp_acct_ledger_details', array(
        'ledger_id'   => $item_data['ledger_id'],
        'trn_no'      => $expense_data['voucher_no'],
        'particulars' => $expense_data['particulars'],
        'debit'       => $item_data['amount'],
        'credit'      => 0,
        'trn_date'    => $expense_data['trn_date'],
        'created_at'  => $expense_data['created_at'],
        'created_by'  => $expense_data['created_by'],
        'updated_at'  => $expense_data['updated_at'],
        'updated_by'  => $expense_data['updated_by'],
    ) );

}

/**
 * Update expense/s data into ledger
 *
 * @param array $expense_data
 * * @param array $expense_no
 * @param array $item_data
 *
 * @return mixed
 */
function erp_acct_update_expense_data_into_ledger( $expense_data, $expense_no, $item_data = [] ) {
    global $wpdb;

    // Update amount in ledger_details
    $wpdb->update( $wpdb->prefix . 'erp_acct_ledger_details', array(
        'ledger_id'   => $item_data['ledger_id'],
        'particulars' => $expense_data['particulars'],
        'debit'       => $item_data['amount'],
        'credit'      => 0,
        'trn_date'    => $expense_data['trn_date'],
        'created_at'  => $expense_data['created_at'],
        'created_by'  => $expense_data['created_by'],
        'updated_at'  => $expense_data['updated_at'],
        'updated_by'  => $expense_data['updated_by'],
    ), array(
        'trn_no' => $expense_no,
    ) );

}

/**
 * Insert Expense from account data into ledger
 *
 * @param array $expense_data
 *
 * @return void
 */
function erp_acct_insert_source_expense_data_into_ledger( $expense_data ) {
    global $wpdb;
    // Insert amount in ledger_details
    $wpdb->insert( $wpdb->prefix . 'erp_acct_ledger_details', array(
        'ledger_id'   => $expense_data['trn_by_ledger_id'],
        'trn_no'      => $expense_data['voucher_no'],
        'particulars' => $expense_data['particulars'],
        'debit'       => 0,
        'credit'      => $expense_data['amount'],
        'trn_date'    => $expense_data['trn_date'],
        'created_at'  => $expense_data['created_at'],
        'created_by'  => $expense_data['created_by'],
        'updated_at'  => $expense_data['updated_at'],
        'updated_by'  => $expense_data['updated_by'],
    ) );
}

