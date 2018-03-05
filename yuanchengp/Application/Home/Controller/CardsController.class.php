<?php
//价格随部门自定义
namespace Home\Controller;
use Think\Controller;
class CardsController extends CommonController {
    public function index(){
        layout(false);
        $this->display();  //"Index/test"
    }
    
    public function receive_ajax() {
        $where['g_card.account']=I("card");
        $where['g_card.pwd']=I("pwd");
        $where['g_card.status']='aleady';
        
        $sql = <<<SQL
            select g_card.id,g_card.account,g_card.state,c_product.name,g_card_info.nums,g_card_info.skuid,g_card_info.pid,g_card.state 
                from g_card  
                left join g_card_info on g_card.id = g_card_info.cid 
                left join c_sku on c_sku.id = g_card_info.skuid 
                left join c_product on c_sku.productid = c_product.id  
                %WHERE%
SQL;
        $card = M()->where($where)->query($sql,TRUE);
        
        if(empty($card)){
            $this->ajaxReturn("empty");
        }else{
            $this->ajaxReturn($card[0]);
        }
        
    }
    public function detil() {
        $where['g_card.id']=I("id");
        $where['g_card.status']='aleady';
        $sql = <<<SQL
    select g_card.validity,g_card.id,g_card.account,g_card.state,c_product.name,g_card_info.nums,g_card_info.skuid,g_card_info.pid,g_card.state,c_picture.path,
                (select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku,
              (select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku,if(sku.stock='off',1,sku.left_num) left_num 
        from g_card  
        left join g_card_info on g_card.id = g_card_info.cid 
        left join c_sku sku on sku.id = g_card_info.skuid 
        left join c_product on sku.productid = c_product.id 
        left join c_picture on c_picture.productid=c_product.id 
        %WHERE% group by sku.id 
SQL;
        $card = M()->where($where)->query($sql,TRUE);
        $card[0]['now'] = date("Y-m-d H:i:s",NOW_TIME);
        $message = M("g_card_message")->find(1);
        $this->assign("message",$message);
        $this->assign("card",$card);
        layout(false);
        $this->display();  //"Index/test"
    }
    public function order_creat() {
        $m = M();
        $m->startTrans();   //开启事务       
        
        $province = I("province");
        $city = I("city");
        $district = I("district");
        $detailed = I("detailed");
        $address_user = I("address_user");
        $phone = I("phone");
        $id = I("id");
//        $this->ajaxReturn($id);die;
        $where['g_card.id']=I("id");
        $where['g_card.status']='aleady';
        $sql = <<<SQL
    select g_card.id,g_card.account,g_card.state,g_card_info.nums,g_card_info.skuid,g_card_info.pid,g_card.state,c_product.maxtypeid,c_product.ascription,sku.price 
        from g_card  
        left join g_card_info on g_card.id = g_card_info.cid 
        left join c_sku sku on sku.id = g_card_info.skuid 
        left join c_product on sku.productid = c_product.id  
        %WHERE%
SQL;
        $card = M()->where($where)->query($sql,TRUE);
        //创建订单
        $userid = M("s_user")->where(array("id_min"=>"hubset"))->getField("id");
        $order_data['ordernum'] = $userid."".substr($card[0]['account'],1);
        $order_data['out_trade_no'] = NUll;
        $order_data['amount'] = 0;
        $order_data['freight'] = 0;
        $order_data['userid'] = $userid;
        $order_data['adminid'] = $card[0]['ascription'];
        $order_data['sdate'] = date("Y-m-d H:i:s",NOW_TIME);
        $order_data['paydate'] = date("Y-m-d H:i:s",NOW_TIME);
        $order_data['statue'] = "shipping";
        $order_data['receiving_address'] = $province." ".$city." ".$district." ".$detailed;
        $order_data['receiving_people'] = $address_user;
        $order_data['receiving_phone'] = $phone;
        $order_data['maxtypeid'] = $card[0]['maxtypeid'];
        $order_data['entersource'] = "service";
        $order_data['card_id'] = $id;
        $order_add = M("b_order")->add($order_data);
        //订单详情
        if($order_add>0){
            $order_info['orderid'] = $order_add;
            $order_info['skuid'] = $card[0]['skuid'];
            $order_info['numbers'] = $card[0]['nums'];
            $order_info['price'] = $card[0]['price'];
            $order_info_add = M("b_order_info")->add($order_info);
            
        }else{
            $m->rollback();
            $this->ajaxReturn("order_error");die;
        }
        //付款
        if($order_info_add>0){
            $amount['userid'] = $userid;
            $amount['amount'] = 0;
            $amount['wx_merchants_order'] = NULL;
            $amount['transaction_id'] = NULL;
            $amount['fdate'] = $order_data['paydate'];
            $amount['openid'] = NULL;
            $amount['type'] = "order";
            
            $f_amount = M("f_amount")->add($amount);            
        } else {
            $m->rollback();
            $this->ajaxReturn("order_info_error");die;            
        }
        //付款详情
        if($f_amount>0){
            $amount_info['userid'] = $userid;
            $amount_info['amountid'] = $f_amount;
            $amount_info['orderid'] = $order_add;
            $amount_info['adminid'] = $card[0]['ascription'];
            $amount_info['amount'] = 0;
            $amount_info['freight'] = 0;
            $f_amount_info = M("f_amount_info")->add($amount_info);
        } else {
            $m->rollback();
            $this->ajaxReturn("famount_error");die;     
        }
        
        if($f_amount_info>0){
            M("g_card")->where(array("id"=>$id))->save(array("state"=>"yes"));
            $m->commit();
            $this->ajaxReturn("OK");die;     
        }else{
            $m->rollback();
            $this->ajaxReturn("famount_info_error");die;     
        }
    }
    public function logistics() {
        $id = I("id");
        $express = M("b_order")->where(array("card_id"=>$id))->field("express")->find();
        
        $express = json_decode($express['express'],TRUE);
        $this->assign("express",$express);
        layout(false);
        $this->display();  //"Index/test"
    }
}