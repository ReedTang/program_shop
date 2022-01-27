<?php

/*
 * 追格商城小程序
 * Author: 追格
 * Help document: https://www.zhuige.com/product/sc.html
 * github: https://github.com/longwenjunjie/zhuige_shop
 * gitee: https://gitee.com/longwenjunj/zhuige_shop
 * License：GPL-2.0
 * Copyright © 2022 www.zhuige.com All rights reserved.
 */

class ZhuiGe_Shop_User_Order_List extends WP_List_Table
{

	public function __construct()
	{
		parent::__construct(array(
			'singular' => '追格商城订单',    // Singular name of the listed records.
			'plural'   => '追格商城订单',    // Plural name of the listed records.
			'ajax'     => false,       		// Does this table support ajax?
		));
	}

	public function get_datas($per_page = 5, $page_number = 1, $search = null)
	{
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}zhuige_shop_user_order WHERE 1=1";

		if ($search) {
			$sql .= " AND `trade_no` LIKE '%" . $search . "%'";
		}

		$sql = $this->parseZGZT($sql);

		if (!empty($_REQUEST['orderby'])) {
			$sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
			$sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
		} else {
			$sql .= ' ORDER BY createtime DESC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

		$result = $wpdb->get_results($sql, 'ARRAY_A');

		return $result;
	}

	public function get_columns()
	{
		$columns = array(
			'cb'        => '<input type="checkbox" />', // Render a checkbox instead of text.
			// 'id'		    => 'ID',
			'trade_no'		=> '订单号',
			'user'	    	=> '用户',
			'goods'			=> '商品',
			'remark'		=> '备注',
			'addressee'		=> '收件人',
			'express'		=> '快递',
			'status'		=> '状态',
			'createtime'	=> '创建时间'
		);

		return $columns;
	}

	protected function get_sortable_columns()
	{
		$sortable_columns = array(
			'createtime'  => array('createtime', false),
		);

		return $sortable_columns;
	}

	protected function column_default($item, $column_name)
	{
		switch ($column_name) {
			// case 'id':
			case 'trade_no':
			case 'remark':
				return $item[$column_name];
			default:
				return print_r($item, true); // Show the whole array for troubleshooting purposes.
		}
	}

	protected function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'order_ids',  				// Let's simply repurpose the table's singular label ("movie").
			$item['id']                 // The value of the checkbox should be the record's ID.
		);
	}

	protected function column_user($item)
	{
		// $avatar = ZhuiGe_Shop::user_avatar($item['user_id']);
		$nickname = get_user_meta($item['user_id'], 'nickname', true);
		// return "<img src='$avatar' style='width:48px;height:48px;'/><div>昵称：$nickname </div>";
		return $nickname;
	}

	protected function column_goods($item)
	{
		$goods_list = unserialize($item['goods_list']);
		// $content = "<ol>";
		// foreach ($goods_list as $goods) {
		// 	$content .= "<li>";
		// 	$content .= "<img src='" . $goods['thumb'] . "' style='width:48px;height:48px;'/>";
		// 	$content .= "<div>" . $goods['name'] . "</div>";
		// 	$content .= "<div>" . $goods['price'] . "元 X " . $goods['count'] . "</div>";
		// 	$content .= "</li>";
		// }
		// $content .= "</ol>";

		$content = "<table>";
		foreach ($goods_list as $goods) {
			$content .= "<tr>";
			$content .= "<td><img src='" . $goods['thumb'] . "' style='width:48px;height:48px;'/></td>";
			$content .= "<td>" . $goods['name'] . "-";
			$content .= "" . $goods['price'] . "元 X " . $goods['count'] . "</td>";
			$content .= "</tr>";
		}
		$content .= "</table>";

		return $content;
	}

	protected function column_addressee($item)
	{
		$value = "<div>收件人：" . $item['addressee'] . "</div>";
		$value .= "<div>手机号：" . $item['mobile'] . "</div>";
		$value .= "<div>地址：" . $item['address'] . "</div>";
		return $value;
	}

	protected function column_express($item)
	{
		$value = "<div>" . $item['express_type'] . "</div>";
		$value .= "<div>快递单号：" . $item['express_no'] . "</div>";

		$page = wp_unslash($_REQUEST['page']); // WPCS: Input var ok.

		// Build edit row action.
		$edit_query_args = array(
			'page'   => $page,
			'action' => 'edit',
			'id'  => $item['id'],
		);

		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url(wp_nonce_url(add_query_arg($edit_query_args, 'admin.php'), 'edit_' . $item['id'])),
			'编辑'
		);

		$value .= $this->row_actions($actions);

		return $value;
	}

	protected function column_status($item)
	{
		$status = '未知';
		if ($item['paytime']) {
			if ($item['express_type'] && $item['express_no']) {
				if ($item['confirmtime']) {
					$status = '已确认收货';
				} else {
					$status = '待收货';
				}
			} else {
				$status = '<span style="color:#CC0000">待发货</span>';
			}
		} else {
			if ($item['canceltime']) {
				$status = '<span style="color:#CCCCCC">已取消</span>';
			} else {
				$status = '待付款';
			}
		}

		return $status;
	}

	protected function column_createtime($item)
	{
		return date("Y-m-d H:i:s", $item['createtime']);
	}

	protected function get_bulk_actions()
	{
		$actions = array(
			'bulk_delete' => '删除',
		);

		return $actions;
	}

	protected function process_bulk_action()
	{
		$action = isset($_GET['action']) ? $_GET['action'] : '';
		if ('bulk_delete' == $action) {
			if (isset($_GET['order_ids'])) {
				$order_ids = $_GET['order_ids'];

				global $wpdb;
				foreach ($order_ids as $order_id) {
					$wpdb->delete("{$wpdb->prefix}zhuige_shop_user_order", ['id' => $order_id], ['%d']);
				}
			}

			$page = wp_unslash($_REQUEST['page']);
			$query = ['page' => $page];
			$redirect = add_query_arg($query, admin_url('admin.php'));
			echo '<script>window.location.href="' . $redirect . '"</script>';
		}
	}

	function prepare_items($search = null)
	{
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		$per_page = 10;
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count($search);

		$this->items = $this->get_datas($per_page, $current_page, $search);

		$this->set_pagination_args(array(
			'total_items' => $total_items,                     // WE have to calculate the total number of items.
			'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
			'total_pages' => ceil($total_items / $per_page),   // WE have to calculate the total number of pages.
		));
	}

	/**
	 * Callback to allow sorting of example data.
	 *
	 * @param string $a First value.
	 * @param string $b Second value.
	 *
	 * @return int
	 */
	protected function usort_reorder($a, $b)
	{
		// If no sort, default to title.
		$orderby = !empty($_REQUEST['orderby']) ? wp_unslash($_REQUEST['orderby']) : 'title'; // WPCS: Input var ok.

		// If no order, default to asc.
		$order = !empty($_REQUEST['order']) ? wp_unslash($_REQUEST['order']) : 'asc'; // WPCS: Input var ok.

		// Determine sort order.
		$result = strcmp($a[$orderby], $b[$orderby]);

		return ('asc' === $order) ? $result : -$result;
	}

	public function record_count($search)
	{
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}zhuige_shop_user_order WHERE 1=1";

		if ($search) {
			$sql .= " AND `trade_no` LIKE '%" . $search . "%'";
		}

		$sql = $this->parseZGZT($sql);

		return $wpdb->get_var($sql);
	}

	/**
	 * 修改表格样式
	 */
	protected function get_table_classes() {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		return array( 'widefat', 'striped', $mode_class, $this->_args['plural'] );
	}

	protected function get_views()
	{
		$links = [];

		$all = '';
		$daifu = '';
		$daifa = '';
		$daishou = '';
		$queren = '';
		$quxiao = '';
		if (!isset($_REQUEST['zgzt'])) {
			$all = 'current';
		} else {
			if ($_REQUEST['zgzt'] == 'daifu') {
				$daifu = 'current';
			} else if ($_REQUEST['zgzt'] == 'daifa') {
				$daifa = 'current';
			} else if ($_REQUEST['zgzt'] == 'daishou') {
				$daishou = 'current';
			} else if ($_REQUEST['zgzt'] == 'queren') {
				$queren = 'current';
			} else if ($_REQUEST['zgzt'] == 'quxiao') {
				$quxiao = 'current';
			}
		}

		$allLink = remove_query_arg(array('_wp_http_referer', '_wpnonce', 'paged', 'zgzt'), wp_unslash($_SERVER['REQUEST_URI']));
		$links[] = '<a href="' . $allLink . '" class="' . $all . '">全部</a>';

		$daifuLink = add_query_arg(array('zgzt' => 'daifu'), $allLink);
		$links[] = '<a href="' . $daifuLink . '" class="' . $daifu . '">待付款</a>';

		$daifaLink = add_query_arg(array('zgzt' => 'daifa'), $allLink);
		$links[] = '<a href="' . $daifaLink . '" class="' . $daifa . '">待发货</a>';

		$daishouLink = add_query_arg(array('zgzt' => 'daishou'), $allLink);
		$links[] = '<a href="' . $daishouLink . '" class="' . $daishou . '">待收货</a>';

		$querenLink = add_query_arg(array('zgzt' => 'queren'), $allLink);
		$links[] = '<a href="' . $querenLink . '" class="' . $queren . '">已确认</a>';

		$quxiaoLink = add_query_arg(array('zgzt' => 'quxiao'), $allLink);
		$links[] = '<a href="' . $quxiaoLink . '" class="' . $quxiao . '">已取消</a>';

		return $links;
	}

	private function parseZGZT($sql)
	{
		if (!isset($_REQUEST['zgzt'])) {
			return $sql;
		}
		$zgzt = $_REQUEST['zgzt'];

		if ($zgzt == 'daifu') {
			$sql .= " AND paytime is null AND canceltime is null";
		} else if ($zgzt == 'daifa') {
			$sql .= " AND paytime is not null AND (express_type='' OR express_no='')";
		} else if ($zgzt == 'daishou') {
			$sql .= " AND paytime is not null AND (express_type!='' AND express_no!='') AND confirmtime is null";
		} else if ($zgzt == 'queren') {
			$sql .= " AND paytime is not null AND (express_type!='' AND express_no!='') AND confirmtime is not null";
		} else if ($zgzt == 'quxiao') {
			$sql .= " AND paytime is null AND canceltime is not null";
		}

		return $sql;
	}
}