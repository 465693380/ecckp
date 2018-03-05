<?php
//当前版本为  
//价格设定按照部门自定义（后台选择）  数据库SKU表的后五个字段已经不需要了
//商品下单 会拆成一个商品一张订单。   原订单模式为 方法creet_order_BACK（ 中商品金额按照部门自定义）
//含商品评论 评论可审核屏蔽
//模式切换为回调模式。
//包含后台可根据部门设置商品是否对其可见
namespace Home\Controller;
use Think\Controller;
class ProductController extends CommonController {

    public function index(){
        $_SESSION['cart'] = array();$_SESSION['skuidstr']=array();
        
        
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
//        dump(I('vals'));
//        exit();
        if(I('vals')){
            $where["concat(c_product.name,c_type.name)"]=array("like","%".I('vals')."%");
        }
        if(I('key')){
            $where["c_product.name"]=array("like","%".I('key')."%");
        }
        
        if(!empty($_GET['typeid'])){
            $class_url = M("c_type")->where("id = {$_GET['typeid']}")->getField("class_url");
            $sql_type = <<<SQL
                select group_concat(id separator ',') id from c_type where class_url like '{$class_url}%'  
SQL;
        $typeid = M()->query($sql_type,TRUE);

            $where["c_product.typeid"]=array("in",$typeid[0]['id']);
        }

        $where["c_product.state"]="上架";
        $where["c_product.status"]="1";
        $where["sku.state"]="yes";
        $where["if(isnull(ps.price)=1,sku.price,ps.price)"]=array("neq",0);
        $sql = <<<SQL
              select c_type.name type_name,if(c_product.limit_status='no','无限制',c_product.limits) limits,sku.productid as pid,c_product.name,c_picture.path,sku.id skuid,if(sku.stock='off','不限库存',sku.left_num) num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,
              (select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku,
              (select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku 
              from c_sku sku 
              left join c_product on sku.productid=c_product.id 
              left join c_type on c_type.id=c_product.typeid 
              left join c_picture on sku.productid=c_picture.productid 
              left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid )
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id 
              %WHERE% group by sku.id 
SQL;
        $sku = M()->where($where)->query($sql,TRUE);
        //dump($sku);
        $this->assign("app",__ROOT__);
        $this->assign("product",$sku);
        $this->display();
    
    }
	public function searchpd(){
        $_SESSION['cart'] = array();$_SESSION['skuidstr']=array();
        
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
        
        if(!empty($_POST['names'])){
            $sql = "select sku.productid as pid,c_product.name,c_picture.path,sku.id skuid,sku.left_num num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,"
                ."(select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku,"
                ."(select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku "    
                ."from c_sku sku "
                ."join c_product on sku.productid=c_product.id "
                ."left join c_picture on sku.productid=c_picture.productid "
                ."left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid )
                and departmentid in ({$strpath}) 
                    ) ps on ps.skuid = sku.id "    
                ."where c_product.state='上架' "
                ."and c_product.status=1 and sku.state='yes' "
                ."and c_product.name like %{$_POST['names']}% "
                ."group by sku.id ";
        }else{
            $sql = "select sku.productid as pid,c_product.name,c_picture.path,sku.id skuid,sku.left_num num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,"
                ."(select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku,"
                ."(select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku "
                ."from c_sku sku "
                ."join c_product on sku.productid=c_product.id "
                ."left join c_picture on sku.productid=c_picture.productid "
                ."left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id "
                ."where c_product.state='上架' "
                ."and c_product.status=1 and sku.state='yes' "
                ."group by sku.id ";
        }
        
        $sku = M()->query($sql);
		echo JSON_encode($sku);
        //dump($sku);
        
    
    }
    public function classify_center() {
        header('Access-Control-Allow-Origin:*');  //允许跨域访问
        
        $_SESSION['cart'] = array();$_SESSION['skuidstr']=array();
        if(empty($_SESSION['userid'])){
            $_SESSION['userid'] = 1;
            $_SESSION['thresh_control'] = "yes";
            $_SESSION['username'] = "平金水";
            $_SESSION['wx_authoid'] = "f-190198213633028337";
            $_SESSION['level'] = "normal";
            $_SESSION['departmentid'] = 213;
            $_SESSION['user_type'] = "no";
        }
        $host = "localhost";   //eshop.feihe.com
        $array['host'] = $host;
        
        
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
        
        $welfare = M("s_user")->where(array("id"=>$_SESSION['userid']))->getField("cmmilk");
        if($welfare=="no"){
            $where = array("state"=>1,"class_lvl"=>2,"parentid"=>array("NEQ",6));
        }else{
            $where = array("state"=>1,"class_lvl"=>2);
        }
        //通过身份判断是否显示福利分类
        $c_type = M("c_type")->where($where)->select();
        //查询第一个默认分类的商品
        $array['type'] = $c_type;
        $type_id = I("type_id");
        if(empty($type_id)){
            $types = $c_type[0]['id'];
        }else{
            $types = $type_id;
        }
        $where_p["c_product.typeid"]=$types;   //$c_type[0]['id']
        $where_p["c_product.state"]="上架";
        $where_p["c_product.status"]="1";
        $where_p["sku.state"]="yes";
        $where_p["if(isnull(ps.price)=1,sku.price,ps.price)"]=array("neq",0);
        $sql = <<<SQL
            select ps.departmentclass,ps.price se_price,c_type.name type_name,if(c_product.limit_status='no','无限制',c_product.limits) limits,sku.productid as pid,c_product.name,c_picture.path,sku.id skuid,if(sku.stock='off','不限库存',sku.left_num) num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,
            (select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku,
            (select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku 
            from c_sku sku 
            join c_product on sku.productid=c_product.id 
            join c_type on c_product.typeid=c_type.id 
            left join c_price_select on c_price_select.skuid=sku.id 
            left join c_picture on sku.productid=c_picture.productid 
            left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id 
            %WHERE% group by sku.id
            
SQL;
             
        $sku = M()->where($where_p)->query($sql,TRUE);
        $array['product'] = $sku;
        dump($array);
    }
    public function classify() {
        $_SESSION['cart'] = array();$_SESSION['skuidstr']=array();
        
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);

        $welfare = M("s_user")->where(array("id"=>$_SESSION['userid']))->getField("cmmilk");
        if($welfare=="no"){
            $where = array("state"=>1,"class_lvl"=>2,"parentid"=>array("NEQ",6));
        }else{
            $where = array("state"=>1,"class_lvl"=>2);
        }
        //通过身份判断是否显示福利分类
        $c_type = M("c_type")->where($where)->select();
        //查询第一个默认分类的商品
        $where_p["c_product.typeid"]=$c_type[0]['id'];   //$c_type[0]['id']
        $where_p["c_product.state"]="上架";
        $where_p["c_product.status"]="1";
        $where_p["sku.state"]="yes";
        $where_p["if(isnull(ps.price)=1,sku.price,ps.price)"]=array("neq",0);
        $sql = <<<SQL
            select ps.departmentclass,ps.price se_price,c_type.name type_name,if(c_product.limit_status='no','无限制',c_product.limits) limits,sku.productid as pid,c_product.name,c_picture.path,sku.id skuid,if(sku.stock='off','不限库存',sku.left_num) num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,
            (select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku,
            (select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku 
            from c_sku sku 
            join c_product on sku.productid=c_product.id 
            join c_type on c_product.typeid=c_type.id 
            left join c_price_select on c_price_select.skuid=sku.id 
            left join c_picture on sku.productid=c_picture.productid 
            left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id 
            %WHERE% group by sku.id
            
SQL;
             
        $sku = M()->where($where_p)->query($sql,TRUE);
        //dump($sku);
//        echo M()->_sql();
//   exit();
        $this->assign("app",__ROOT__);
        $this->assign("type",$c_type);
        $this->assign("product",$sku);
        $this->display();
    }
    public function classify_ajax() {
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
        
        $where_p["c_product.typeid"]=$_POST['typeid'];
        $where_p["c_product.state"]="上架";
        $where_p["c_product.status"]="1";
        $where_p["sku.state"]="yes";
        $where_p["if(isnull(ps.price)=1,sku.price,ps.price)"]=array("neq",0);
        $sql = <<<SQL
              select c_type.name type_name,if(c_product.limit_status='no','无限制',c_product.limits) limits,sku.productid as pid,c_product.name,c_picture.path,sku.id skuid,if(sku.stock='off','不限库存',sku.left_num) num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,
              IFNULL((select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ),'') this_sku,
              IFNULL((select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ),'') auto_sku 
              from c_sku sku 
              join c_product on sku.productid=c_product.id 
              join c_type on c_product.typeid=c_type.id 
              left join c_picture on sku.productid=c_picture.productid 
              left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id 
              %WHERE% group by sku.id
SQL;
        
        
        $sku = M()->where($where_p)->query($sql,TRUE);
        //dump($sku);
        $this->ajaxReturn($sku);
    }
    public function product_detil() {
        $_SESSION['cart'] = array();
        if(empty($_SESSION['userid'])){
            echo "<h3 style='text-align:center;margin-top:20px;'>请在微信入口进入</h3>";
            exit();
        }
        //确定价格取自哪个部门
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
        
        $sql = <<<SQL
                select c_product.limits,c_product.limit_status,c_type.name type_name,sku.productid pid,c_product.name,c_product.detail,c_picture.path,sku.id skuid,if(sku.stock='off','不限库存',sku.left_num) left_num,sku.sell_num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.ascription,if(c_freight.status='no','0',c_freight.m_first) m_first,if(c_freight.status='no','0',c_freight.m_other) m_other,
                (select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku, 
                (select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku 
                from c_sku sku 
                join c_product on sku.productid=c_product.id 
                join c_type on c_type.id=c_product.typeid 
                left join c_picture on sku.productid=c_picture.productid 
                left join c_freight on c_freight.product_id=c_product.id 
                left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id 
                where c_product.state='上架' and sku.id={$_GET['skuid']} and c_freight.state='yes' and sku.state='yes' 
SQL;

        $sku = M()->query($sql);

        if ($sku) {
            $pic_all = M('cPicture')->where('productid='.$_GET['pid'])->getfield('path',true);
            //自动选择属性列表
            $sqls = "select sku.id skuid,sku.left_num num,sku.left_num,sku.sell_num,if(isnull(ps.price)=1,sku.price,ps.price) price,
                (select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_auto attr on attr.id = pattr.attribute_auto_id where pattr.skuid = sku.id ) auto_sku 
                from c_sku sku
                left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                    and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id 
                where sku.productid = {$_GET['pid']} and sku.state='yes' 
                group by sku.id";
            $auto = M()->query($sqls);
            
        }else{
            $this->assign('msg',"null");
        }
        $sku[0]['detail'] = $sku[0]['detail'];
        //评论
                $sqlcomm = <<<SQL
    select * from s_comment where skuid={$_GET['skuid']} and status=1 order by date
SQL;
        $comment = M()->query($sqlcomm);
        
        //dump($_GET['skuid']);
        $this->assign("app",__ROOT__);
        $this->assign("pic_all",$pic_all);
        $this->assign("detil",$sku[0]);
        $this->assign("comment",$comment);
        $this->assign("auto",$auto);
        $this->display();
    }
    public function adress_select() {
        $b_address = M("b_address")->where(array("id"=>$_POST['id']))->find();
        $_SESSION['address'] = $b_address;
        $this->ajaxReturn($b_address);
    }

    public function order_preview() {
        
        $_SESSION['address']=array();
        
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
        
        if(empty($_REQUEST['source'])){
            $_REQUEST['source'] = $_SESSION['souce'];
        }
        if($_REQUEST['source']=="buynow"){
            if(!empty($_POST['skuid'])){
                $_SESSION['skuid_buynow'] = $_POST['skuid'];
            }
            
            
            $sql = "select ps.departmentid,sku.productid pid,c_product.name,group_concat(c_picture.path separator '|') path,sku.id skuid,sku.left_num num,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.state,c_product.ascription,m_admin.nickname,m_admin.id mid,if(c_freight.status='no','0',c_freight.m_first) m_first,if(c_freight.status='no','0',c_freight.m_other) m_other,"
                ."(select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku, "
                ."(select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku "
                ."from c_sku sku "
                ."join c_product on sku.productid=c_product.id "
                ."left join c_picture on sku.productid=c_picture.productid "
                ."join m_admin on m_admin.id=c_product.ascription "
                ."left join c_freight on c_freight.product_id=c_product.id "
                ."left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                    and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id "
                ."where sku.id in ({$_SESSION['skuid_buynow']})   and c_freight.state='yes'"; 
                
                $skuT = M()->query($sql);
                if(!empty($_POST['buynum'])){
                    $_SESSION['cart'][$skuT[0]['mid']][$skuT[0]['skuid']] = $_POST['buynum'];
                    $skuT[0]['buynum'] = $_POST['buynum'];
                }
                $_SESSION['skuidstr'] = $skuT[0]['skuid'];
                $skuT[0]['pathlist'] = explode("|",$skuT[0]['path']);
                $skuT[0]['path'] = $skuT[0]['pathlist'][0];
               
                //dump($_SESSION['cart']);
                
                $first = $skuT[0]['m_first'];
                $other = ($_SESSION['cart'][$skuT[0]['mid']][$skuT[0]['skuid']]-1)*$skuT[0]['m_other'];
                //dump($first);
                $mon_fright = $first+$other;                
                $_SESSION['souce'] = "buynow";
                $totalprice = $skuT[0]['price']*$_SESSION['cart'][$skuT[0]['mid']][$skuT[0]['skuid']];
                $sku[$skuT[0]['mid']] = $skuT;
                
        }elseif ($_REQUEST['source']=="buycar") {
            $_SESSION['souce'] = "buycar";
 
            $sql = "select c_product.maxtypeid,c_product.typeid,sku.productid pid,c_product.name,c_picture.urlsmall path,sku.id skuid,if(isnull(ps.price)=1,sku.price,ps.price) price,c_product.typeid,c_product.state,m_admin.nickname,m_admin.id mid,sku.left_num,if(c_freight.status='no','0',c_freight.m_first) m_first,if(c_freight.status='no','0',c_freight.m_other) m_other,"
                ."(select group_concat(pattr.val,attr.name separator '|') from c_product_attribute_v pattr left join c_attribute_v attr on attr.id = pattr.attribute_v_id  where pattr.skuid = sku.id ) this_sku, "
                ."(select group_concat(pattrs.val,atuu.name separator '|') from c_product_attribute_v pattrs left join c_attribute_auto atuu on atuu.id = pattrs.attribute_auto_id  where pattrs.skuid = sku.id ) auto_sku "
                ."from c_sku sku "
                ."join c_product on sku.productid=c_product.id "
                ."left join c_picture on sku.productid=c_picture.productid "
                ."join m_admin on m_admin.id=c_product.ascription "
                ."left join c_freight on c_freight.product_id=c_product.id "
                ."left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                    and departmentid in ({$strpath}) 
                ) ps on ps.skuid = sku.id "
                ."where sku.id in ({$_SESSION['skuidstr']})   and c_freight.state='yes' group by sku.id";
                
                $skuT = M()->query($sql);
                $totalprice = 0;
                foreach ($skuT as $key => $value) {
                    $skuT[$key]['buynum'] = $_SESSION['cart'][$value['mid']][$value['skuid']];
                    $money = $value['price']*$skuT[$key]['buynum'];
                    $totalprice = $totalprice+$money;
                    if($value['maxtypeid']=="6"){
                        $sku[$value['mid']."-".$value['maxtypeid']][] = $skuT[$key];
                    }else{
                        $sku[$value['mid']][] = $skuT[$key];
                    }
                    
                                        
                }
                
                
               //运费计算
                $mon_fright=0;                
                foreach ($sku as $key => $value) {  //此循环是订单层
                    $nums = 0;$m_first=0;
                    foreach ($value as $k => $v) {   //此循环是商品层
                        
                        if($v['m_first']>=$m_first){
                            $m_first = $v['m_first'];
                            $fright[$key]['m_first'] = $v['m_first']; 
                            $fright[$key]['m_other'] = $v['m_other'];
                        }
                        $nums = $nums+$v['buynum'];
                        
                    }
                    
                    $fright[$key]['numtoatl'] = $nums;
                    $fright[$key]['monkey'] = ($fright[$key]['numtoatl']-1)*$fright[$key]['m_other']+$fright[$key]['m_first'];
                    //dump($fright[$key]);
                    $mon_fright = $mon_fright+$fright[$key]['monkey'];
                }
        }
//        dump($sku);
//        exit();
        $totalprice= $totalprice+$mon_fright;
        $address = M("b_address")->where(array("userid"=>$_SESSION['userid'],"acquie"=>"acquie"))->find();
        
        $_SESSION['address'] = $address;
        
        $address_hid = M("b_address")->where(array("userid"=>$_SESSION['userid']))->select();
        
        $this->assign("app",__ROOT__);
        $this->assign("sku",$sku);
        $this->assign("mon_fright",$mon_fright);
        $this->assign("totalprice",$totalprice);
        $this->assign("address_hid",$address_hid);
        $this->assign("address",$address);
        
        //支付的各项数据
        
        //统一下单接口
        $openid = $_SESSION['user_type']?$_SESSION['wx_openid']:userid_to_openid($_SESSION['wx_authoid']);
        $paydata['appid'] = $_SESSION['user_type']?"wx9c46f3b853408432":"wx1d3cad9bcca15fbc";
        $paydata['body'] = "成员福利购";
        $paydata['fee_type'] = "CNY";
        $paydata['mch_id'] = $_SESSION['user_type']?"1491122532":"1488455602";
        $paydata['nonce_str'] = generate_password(30);
        $paydata['notify_url'] = "http://eshop.feihe.com/app/index.php/Product/notify";
        $paydata['out_trade_no'] = rand(100000000,999999999)."".NOW_TIME;
        $paydata['sign_type'] = "MD5";
        $paydata['total_fee'] = $totalprice*100;//  $totalprice*100;
        $paydata['spbill_create_ip'] = get_client_ip();
        $paydata['trade_type'] = "JSAPI";
        $paydata['openid'] = $_SESSION['user_type']?$_SESSION['wx_openid']:$openid['openid'];
        ksort($paydata);
        foreach ($paydata as $key => $value) {
            if(empty($sign)){
                $sign = $key."=".$value;
            }else{
                $sign = $sign."&".$key."=".$value;
            }
            
        }
        $paydata['sign'] = $sign."&key=hCNVlSe0S0t2iqHDxDVerxawmEfKuM2R";
        $paydata['sign'] = strtoupper(md5($paydata['sign']));
        $this->assign("paydata",$paydata);
        
        $xml = arrayToXml($paydata);
        
        $result = curl_post("https://api.mch.weixin.qq.com/pay/unifiedorder", $xml);

//下面是为H5调起支付做准备的数据
        
        
        $resultarr = xmlToArray($result);
                
        $payfor['appId']=$resultarr['appid'];
        $payfor['timeStamp']=NOW_TIME;
        $payfor['nonceStr']=$resultarr['nonce_str'];
        $payfor['package']="prepay_id=".$resultarr['prepay_id'];
        $payfor['signType']="MD5";
        ksort($payfor);
        foreach ($payfor as $key => $value) {
            if(empty($sign_payfor)){
                $sign_payfor = $key."=".$value;
            }else{
                $sign_payfor = $sign_payfor."&".$key."=".$value;
            }
            
        }
        
        $sign_payfor = $sign_payfor."&key=hCNVlSe0S0t2iqHDxDVerxawmEfKuM2R";
        
        $payfor['paySign']= strtoupper(md5($sign_payfor));
        $this->assign("payfor",$payfor);

        $this->display();
    }
    public function notify() {
        $xml = file_get_contents('php://input');

        $postarr = xmlToArray($xml);   //$xml
        
        if($postarr['result_code']=="SUCCESS"&&$postarr['return_code']=="SUCCESS"){
            //创建f_amount        
            $m = M();
            $m->startTrans();   //开启事务
            
            
            $user = M("b_order")->where(array("out_trade_no"=>$postarr['out_trade_no']))->field("userid")->find();
            $data['userid']=$user['userid'];
            $data['amount']=$postarr['total_fee'];
            $data['wx_merchants_order']=$postarr['out_trade_no'];
            $data['transaction_id']= $postarr['transaction_id'];
            $data['fdate']= date("Y-m-d H:i:s",NOW_TIME);
            $data['openid']= $postarr['openid'];
            $data['type']= 'order';
            S("data",$data);
//            dump($data);
//            exit();
            //创建
            $add = M("f_amount")->add($data);
            
//            $add=1;
            if($add>0){
                //查询资金详情
                $sql = <<<SQL
                select b_order.amount,b_order.adminid,b_order_info.numbers,b_order_info.orderid,b_order_info.skuid skuid,if(c_freight.status='no','0',c_freight.m_first) m_first,if(c_freight.status='no','0',c_freight.m_other) m_other 
                from b_order_info 
                left join b_order on b_order.id=b_order_info.orderid 
                left join c_sku on c_sku.id=b_order_info.skuid 
                left join c_freight on c_freight.product_id=c_sku.productid 
                where b_order.out_trade_no={$postarr['out_trade_no']}
SQL;
                $skuT = M()->query($sql);
                
                //处理运费
                    foreach ($skuT as $k => $v) {
                        $new[$v['orderid']]['adminid'] = $v['adminid'];
                        $new[$v['orderid']]['amount'] = $v['amount'];
                        if(empty($new[$v['orderid']]['numbers'])){
                            $new[$v['orderid']]['numbers'] = $v['numbers'];
                        }else{
                            $new[$v['orderid']]['numbers']= $v['numbers']+$new[$v['orderid']]['numbers'];
                        }
                         
                        if($v['m_first']>=$new[$v['orderid']]['m_first']){
                            $new[$v['orderid']]['orderid'] = $v['orderid'];
                            
                            $new[$v['orderid']]['m_first'] = $v['m_first'];
                            $new[$v['orderid']]['m_other'] = $v['m_other'];
                            
                        }
                        $new[$v['orderid']]['fright'] = ($new[$v['orderid']]['numbers']-1)*$new[$v['orderid']]['m_other']+$new[$v['orderid']]['m_first'];
                    }

                    //创建info 
                    $qs = 0;
                    foreach ($new as $key => $value) {
                        $amount_info[$qs]['userid'] = $user['userid'];
                        $amount_info[$qs]['amount'] = $value['amount'];
                        $amount_info[$qs]['freight'] = $value['fright'];
                        $amount_info[$qs]['amountid'] = $add;
                        $amount_info[$qs]['orderid'] = $key;
                        $amount_info[$qs]['adminid'] = $value['adminid'];
                        $qs= $qs+1;
                    }
//                    S("detil_info",$amount_info);
//                                        dump($amount_info);
//                exit();
                    $detil = M("f_amount_info")->addAll($amount_info);
//                    S("detil",$detil);
                    if($detil>0){
                        M("b_order")->where(array("out_trade_no"=>$postarr['out_trade_no']))->save(array("statue"=>"shipping","paydate"=>$data['fdate']));
                        
                        $m->commit();
                        $return['return_code']='SUCCESS';
                        echo arrayToXml($return);
                    } else {
                        $m->rollback();
                    }
            }else{
                $m->rollback();
            }
      
        }
        
    }
    
    public function creat_order() {
        $m = M();
        $m->startTrans();   //开启事务                
        $out_trade_no = I("out_trade_no");
        
        
          //确定价格取自哪个部门
        $organizational = M("organizational")->where(array("id"=>$_SESSION['departmentid']))->find();  //查询部门path
        $newstrpath = substr($organizational["path"],0,strlen($organizational["path"])-1);             //去掉path 最后的 -
        $strpath = str_replace("-", ",", $newstrpath);
        
        $where['c_sku.id'] = array("in",$_SESSION['skuidstr']);
        $sql = <<<SQL
                select c_product.maxtypeid,c_product.ascription,c_sku.id skuid,c_sku.productid,if(isnull(ps.price)=1,c_sku.price,ps.price) price from c_sku 
                left join c_product on c_sku.productid=c_product.id 
                left join (select * from c_price_select where (skuid,departmentclass) in 
                (select skuid,max(departmentclass) departmentclass from c_price_select where departmentid in ({$strpath}) group by skuid ) 
                and departmentid in ({$strpath}) 
                ) ps on ps.skuid = c_sku.id 
                    %WHERE% 
SQL;
        $sku = M()->where($where)->query($sql,TRUE);
        
        foreach ($sku as $key => $value) {
            $sku[$key]['buynums'] = $_SESSION['cart'][$value['ascription']][$value['skuid']];
            for($i=0;$i<$sku[$key]['buynums'];$i++){
                $dat['skuid'] = $value['skuid'];
                $dat['productid'] = $value['productid'];
                $dat['price'] = $value['price'];
                $dat['ascription'] = $value['ascription'];
                $dat['maxtypeid'] = $value['maxtypeid'];
                $dat['buynums'] = 1;
                $orderarr[]=$dat;
            } 
        }
        $o = 0;
        foreach ($orderarr as $key => $value) {
            $data = array();
            $data['ordernum']=$_SESSION['userid']."".$key."".rand(1000,9999)."".NOW_TIME;
            $data['out_trade_no']=$out_trade_no;
            $data['userid']=$_SESSION['userid'];
            $data['adminid']=$value['ascription'];
            $data['sdate']=date("Y-m-d H:i:s",NOW_TIME);
            $data['statue']="wait";
        
            $data['receiving_address']=$_SESSION['address']['province']." ".$_SESSION['address']['city']." ".$_SESSION['address']['area']." ".$_SESSION['address']['detailed'];
            $data['receiving_people']=$_SESSION['address']['name'];
            $data['receiving_phone']=$_SESSION['address']['phone'];
            
            $data['maxtypeid']=$value['maxtypeid'];
            $data['amount'] = $value['price'];
            $data['freight'] = 0;
            if($_SESSION['user_type'] == 'no'){
                $data['entersource'] = 'service';   //公众号
            }else{
                $data['entersource'] = 'enterprise';   //企业号
            }
            
            $order = M("b_order")->add($data);
            if($order>0){
                $inof['orderid'] = $order;
                $inof['skuid'] = $value['skuid'];
                $inof['numbers'] = $value['buynums'];
                $inof['price'] = $value['price'];
                $order_info = M("b_order_info")->add($inof);
                if($order_info>0){
                    if(empty($orderid_str)){
                        $orderid_str = $order;
                    }else{
                        $orderid_str = $orderid_str.",".$order;
                    }
                      $o = $o+1;
                }else{
                    $m->rollback();
                    $return_order['msg'] = "order_creat";  //订单写入失败
                }
            } else {
                 $m->rollback();
                 $return_order['msg'] = "order_creat";  //订单创建失败
            }

            

        }
        if($o==count($orderarr)){  //订单成功个数
            //订单写入成功  更改购物车状态
            if($_SESSION['souce'] = "buycar"){

                $buycar = M("b_buycart")->where(array("userid"=>$_SESSION['userid'],"skuid"=>array("in",$_SESSION['skuidstr'])))->save(array("status"=>"clear"));

            }

            //订单写入成功  更改销售数量
            //销售数量
            $sellsql = "update c_sku set sell_num=case id "; 
            foreach ($sku as $key => $value) {

                $sellsql = $sellsql."when {$value['skuid']} then (sell_num+{$value['buynums']}) ";
            }
            $sellsql = $sellsql."end where id in ({$_SESSION['skuidstr']})";
            M()->execute($sellsql);
            //库存
            $leftsql = "update c_sku set left_num=case id ";
            foreach ($sku as $key => $value) {

                $leftsql = $leftsql."when {$value['skuid']} then (left_num-{$value['buynums']}) ";
            }
            $leftsql = $leftsql."end where id in ({$_SESSION['skuidstr']})";
            M()->execute($leftsql);
            $m->commit();
            $return_order['msg'] = "ok";
            $return_order['orderid'] = $orderid_str;
            unset($_SESSION['cart']);
            unset($_SESSION['skuidstr']);
            unset($_SESSION['souce']);
            unset($_SESSION['address']);

        }else{
            $m->rollback();
            $return_order['msg'] = "order_nums_error";  //订单创建失败
        }

        
        $this->ajaxReturn($return_order);
    }
    
    public function permissions_detect() {
        //时间限制
        $limit = M("b_pay_all_control")->where(array("id"=>1))->find();
        $time = date("Y-m-d H:i:s",NOW_TIME);
        
        if($time>$limit['sdate']&&$time<$limit['edate']){

        } else {
            echo 'timesbeyond';
            exit();
        }
        //购买限制
        $sql_sku_pid = "select c_product.maxtypeid,c_product.typeid,c_product.limits,c_product.limit_status,c_sku.left_num,c_sku.stock from c_sku join c_product on c_product.id=c_sku.productid where c_sku.id={$_POST['skuid']}";
        $sku_pid  = M()->query($sql_sku_pid);    //查询这个商品

        $thresh_control = M('sUser')->where('id='.$_SESSION['userid'])->getfield('thresh_control');
//        $this->ajaxReturn($thresh_control);
//        exit();
        if($thresh_control=="yes"){  //如果受限制  那么开始判断
            if($sku_pid[0]['limit_status']=="yes"){   //如果商品受限制
                $sql = "select COALESCE(sum(b_order_info.numbers),0) sumnums from b_order_info "
                    . "join b_order on b_order.id=b_order_info.orderid "
                    . "where b_order.userid={$_SESSION['userid']} and b_order_info.skuid={$_POST['skuid']} and b_order.sdate BETWEEN '".$limit['sdate']."' AND '".$limit['edate']."'";   

                $aaa  = M()->query($sql);    //查询这个商品
                $oldbuy = $aaa[0]['sumnums']+$_POST['buynum'];  // 这一批  以前买过的数量+这次要购买的数量
                            
                if($oldbuy>$sku_pid[0]['limits']){  //如果购买数量超了
                    $snums = $sku_pid[0]['limits']-$aaa[0]['sumnums'];
                    if($snums<0){
                        $snums = 0;
                    }
                    echo "超出了该商品的购买限制，您只能再购买".$snums."个";
                    exit();
                }
            }

        }
        //库存限制
        if($sku_pid[0]['stock']=="on"){  //on代表限制库存
            if($_POST['buynum']>$sku_pid[0]['left_num']){   //判断库存
                echo 'kclimit';
                exit();
            }
        }
        
        
        
        //福利粉购买权限设置
        
        if($sku_pid[0]['maxtypeid']!=6){  //没买福利粉不需要慌
            echo 'ok';
        }elseif($sku_pid[0]['typeid']==7){   //首先确认买的是福利粉了 如果买的是成人粉   那么 有买福利粉的权限就行
            $s_user = M("s_user")->where(array("id"=>$_SESSION['userid'],"cmmilk"=>"yes"))->count();
            if($s_user>0){   //有
                echo 'ok';
            }else{
                echo 'no';
            }
            
        } elseif($sku_pid[0]['typeid']==8) {
            $s_user = M("s_user")->where(array("id"=>$_SESSION['userid'],"cmmilk"=>"yes","cmbaby"=>"yes"))->count();
            if($s_user>0){   //有
                echo 'ok';
            }else{
                echo 'no';
            }
        }else{
            echo 'ok';
        }
    }

    public function address_load(){
        $address = M("b_address")->where(array("userid"=>$_SESSION['userid'],"acquie"=>"acquie"))->find();
        $this->assign('address',$address);
        $this->display();
    }
    

}