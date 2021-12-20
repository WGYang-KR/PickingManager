<?php
session_start();
  header('Content-Type: text/html; charset=utf-8');
include "DbOperationSub.php";
class DbOperation extends DbOperationSub
{
    private $conn;
    // public $conn;
    public $JinhyunPom;  //public으로 변수 선언
    public $event_prod;  //public으로 변수 선언
    public $evetn_qury;  //public으로 변수 선언
    public $evetn_sel_qury;  //public으로 변수 선언
    public $serviceList;  //public으로 변수 선언
    public static $who;  //public으로 변수 선언
    //쿼리값을 배열로변환
    function fetchDB($stmt)
    {
        $row = array();
        $result = array();
        $meta = $stmt->result_metadata();//객체형태로 변환

        while ($field = $meta->fetch_field()) {//변환된 객체에서 지정되어있는 필드 name을 호출
            $params[] = &$row[$field->name];
        }

        call_user_func_array(array($stmt, 'bind_result'), $params);//받아야하는 인자값을넣어서   bind_result 함수를 호출

        //결과값을 key value 형태로 담아서 반환
        while ($stmt->fetch()) {
            foreach($row as $key => $val)
            {
                $c[$key] = $val;
            }
            $result[] = $c;
        }



        return $result;
    }

    //Constructor
    function __construct()
    {
        require_once dirname(__FILE__) . '/config.php';
        require_once dirname(__FILE__) . '/DbConnect.php';

        // echo $this->aa->a;
        // echo $this->bb->a;
        // echo $this->cc->a;
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
        // $this->serviceList = "성수동1가|성수동2가|사근동|행당동|송정동|화양동|자양동|군자동";
        $this->sungsooList = "성수동1가|성수동2가";
        $this->wang1List = "사근동|행당동";
        $this->songjung = "송정동";
        //2021.02.18 리펙토링
        $this->serviceList = "";
        $this->JinhyunPom = "";

        $LocalArea = $this->selectLocalArea();

        if ($LocalArea == SELECT_FAILED) {
          return;
        } else {
          $LocalArea->bind_result($area_class);

          while ($LocalArea->fetch()) {
            if ($this->serviceList == "") {
              $this->serviceList .= "$area_class";
            }
            else {
              $this->serviceList .= "|$area_class";
            }
          }
        }

        $isSellerSmall = $this->isSellerSmall();//원가설정유통사검색
        if ($isSellerSmall == SELECT_FAILED) {//검색실패
          return;
        }else {//검색성공
          $isSellerSmall -> bind_result($isSellerSmallSel);
          while ($isSellerSmall->fetch()) {//반환되는 아이디값으로 검색.
            $this->JinhyunPom .= "$isSellerSmallSel,";
          }
        }


        //$stmt = $this->conn->prepare("set session character_set_connection=utf8; set session character_set_results=utf8; set session character_set_client=utf8;");
        //$stmt->exectue();
        // 2021. 01. 14일 리팩토링
        //특가상품
        $this->event_prod ="A0310046,E0000000,E0000001,E0000002,E1000000";
        $this->evetn_qury = "CASE  WHEN cart.PROD_CD = 'A0310046' and cart.SELLER_ID = 'deliverylab' THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD = 'E0000000' THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD = 'E0000001' and cart.SELLER_ID = 'deliverylab'  THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD like '%E%' and cart.SELLER_ID = 'eventstore1'  THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD = 'E0000002' THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                            END ";
        $this->evetn_cart_qury = "CASE  WHEN cart.PROD_CD = 'A0310046' and cart.SELLER_ID = 'deliverylab' THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD = 'E0000000' THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD = 'E0000001' and cart.SELLER_ID = 'deliverylab'  THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD like '%E%' and cart.SELLER_ID = 'eventstore1'  THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                                   WHEN cart.PROD_CD = 'E0000002' THEN (SELECT SELLER_PROD_PRICE FROM TB_SELLER_PROD_PRICE WHERE SELLER_PROD_CD = cart.PROD_CD AND SELLER_ID = cart.SELLER_ID )
                            ELSE";
        $this->evetn_sel_qury = "CASE WHEN cart.prod_cd = 'A0310046'  THEN cart.seller_id = 'deliverylab'  WHEN cart.prod_cd = 'E0000000'  THEN cart.seller_id = 'deliverylab'  WHEN cart.prod_cd = 'E0000001'  THEN cart.seller_id = 'deliverylab'  WHEN cart.prod_cd = 'E0000002'  THEN cart.seller_id = 'deliverylab' END";
        DbOperationSub::__construct();
    }
    // 지역거점 전체 출력
    public function selectLocalArea()
    {
      $query = "SELECT AREA_CLASS

                FROM   TB_AREA_CLASS

                WHERE  AREA_CLASS_YN = 'Y'";

      $stmt = $this->conn->prepare($query);

      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows > 0) {
        return $stmt;
      } else {
        return SELECT_FAILED;
      }
    }

    private function isSellerSmall()
    {
        $stmt = $this->conn->prepare("SELECT SELLER_ID FROM TB_SELLER WHERE COSTPR_YN = 'Y'");
        // $stmt->bind_param("sss", $cust_id, $business_name, $tel_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

     public function selectAdminMenu($REQ)
     {
          $adminType = $REQ["adminType"];
          $menuType = $REQ["menuType"];
          $menuNO = $REQ["menuNO"];

          $sql = "SELECT no,admin_menu_no,admin_type,admin_menu_name,admin_menu_sqc,admin_menu_url,admin_use_yn FROM TB_ADMIN_MENU
                    WHERE admin_menu_no is null
                    AND ADMIN_TYPE = '$adminType'
                    ORDER BY admin_menu_sqc  ASC";

          switch ($menuType) {
            case "main":
              break;
            case "sub":
              $sql = "SELECT no,admin_menu_no,admin_type,admin_menu_name,admin_menu_sqc,admin_menu_url,admin_use_yn FROM TB_ADMIN_MENU
                        WHERE admin_menu_no is not null
                        AND ADMIN_TYPE = '$adminType'
                        AND admin_menu_no = '$menuNO'
                        ORDER BY admin_menu_sqc  ASC";
              break;
            default:
              break;
          }
         $stmt = $this->conn->prepare("$sql");
         $stmt->execute();
         $stmt->store_result();
         if ($stmt->num_rows > 0) {
             return $stmt;
         } else {
             return SELECT_FAILED;
         }
     }
     public function cartMSListTmCount($cust_id)
     {
         $stmt = $this->conn->prepare("SELECT DISTINCT sel_pp.ORDER_DEADLINE_TM as tm from (SELECT *
      FROM   TB_CART
      WHERE  cust_id = '$cust_id') cart
      join TB_SELLER_PROD_CD sel_pc
       ON Concat(cart.prod_cd, '_', cart.seller_id) =
          Concat(sel_pc.prod_cd, '_', sel_pc.seller_id)
      join TB_SELLER_PROD_PRICE sel_pp
       ON Concat(sel_pc.seller_prod_cd, '_', sel_pc.seller_id) =
          Concat(sel_pp.seller_prod_cd, '_', sel_pp.seller_id)");
         $stmt->execute();
         $stmt->store_result();
         if ($stmt->num_rows > 0) {
             return $stmt;
         } else {
             return SELECT_FAILED;
         }
     }
    public function cartMSList_All($cust_id,$tm)
    {
      if ($tm > 0) {
            $tmWhere = "(SELECT *
            FROM   TB_SELLER_PROD_PRICE
            WHERE  order_deadline_tm = $tm)";
      }else {
        $tmWhere = "TB_SELLER_PROD_PRICE";
      }
        $stmt = $this->conn->prepare("SELECT cart.seller_id,
       cart.prod_cd,
       pt.prod_name,
       pt.prod_cont,
       pt.prod_wgt,
       cart.prod_count,
       pt.sale_unit,
       $this->evetn_cart_qury(
       IF(Instr('$this->JinhyunPom', sel_pc.seller_id),
       IF(pt.taxfree_yn =
       0, Round(sel_pp.seller_prod_price * 1.1), sel_pp.seller_prod_price),
       ( IF(cart.prod_cd = discunt.prod_cd, IF(pt.taxfree_yn = 0
         , Round(Round(
                   Round(( sel_pp.seller_prod_price / 0.95 ) / (
                               ( 100 - selcust.margin_rate ) * 0.01 ), -1) * ( (
                   100 - discunt.discount_rate ) * 0.01 ), -1) * 1.1)
                 , Round(
                   Round(( sel_pp.seller_prod_price / 0.95 ) / (
                         ( 100 - selcust.margin_rate ) * 0.01 ), -1) * ( (
                   100 - discunt.discount_rate ) * 0.01 ), -1)),
         IF(pt.taxfree_yn = 0, Round(Round(
                                     Round(
                               ( sel_pp.seller_prod_price / 0.95 ) / (
                               ( 100 - selcust.margin_rate ) * 0.01 ), -1),
         -1) * 1.1)
, Round(
  Round(( sel_pp.seller_prod_price / 0.95 ) / (
                          ( 100 - selcust.margin_rate ) * 0.01 ), -1)))) ))) END AS
price,
pt.fact_name,
sel.seller_name,
sel.tel_no,
sel_pp.order_deadline_tm,
selcust.min_order_pr,
pt.img
,
ifnull(
IF(Instr('$this->JinhyunPom', sel_pc.seller_id), sel_pp.seller_prod_price, Round(
Round(( sel_pp.seller_prod_price / 0.95 ) / (
     ( 100 - selcust.margin_rate ) * 0.01 ), -1) * (
( 100 - discunt.discount_rate ) * 0.01 ), -1)),
Round(
Round(( sel_pp.seller_prod_price / 0.95 ) / (
     ( 100 - 0 ) * 0.01 ), -1) * (
( 100 - 0 ) * 0.01 ), -1)),
pt.taxfree_yn,
sel_pp.seller_prod_price,
sel_pp.point_order_yn,
cart.cart_memo
FROM   (SELECT *
 FROM   TB_CART
 WHERE  cust_id = '$cust_id') cart
join TB_PROD pt
  ON cart.prod_cd = pt.prod_cd
join TB_SELLER sel
  ON cart.seller_id = sel.seller_id
join TB_SELLER_PROD_CD sel_pc
  ON Concat(cart.prod_cd, '_', cart.seller_id) =
     Concat(sel_pc.prod_cd, '_', sel_pc.seller_id)
join $tmWhere sel_pp
  ON Concat(sel_pc.seller_prod_cd, '_', sel_pc.seller_id) =
     Concat(sel_pp.seller_prod_cd, '_', sel_pp.seller_id)
join (SELECT *
      FROM   TB_SELLER_BY_CUST
      WHERE  cust_id = '$cust_id') selcust
  ON cart.seller_id = selcust.seller_id
left join (SELECT *
           FROM   TB_PROD_DISCOUNT
           WHERE  cust_id = '$cust_id') discunt
       ON cart.seller_id = discunt.seller_id
          AND cart.prod_cd = discunt.prod_cd
WHERE sel_pc.seller_prod_cd = sel_pp.seller_prod_cd
AND sel_pc.seller_id = sel.seller_id
order by cart.seller_id asc , sel_pp.order_deadline_tm asc,cart.prod_cd");
 //concat(cart.seller_id,'_',cart.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        // $stmt->bind_param("sss",$cust_id,$cust_id,$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CART_NOT_EXIST;
        }
    }
    //Function to create a new user
    public function createUser($cust_id, $pass, $business_name, $owner_name, $addr_cont, $tel_no,
    $addr_cd,$INVITE_RECOMMENDER_CODE,$RECOMMENDER_TEL_NO,$benefit,$email, $ctg_cd)
    {
        if (!$this->isUserExist($cust_id, $business_name, $tel_no)) {
          echo "$cust_id, $pass, $business_name, $owner_name, $addr_cont, $tel_no,$addr_cd,$INVITE_RECOMMENDER_CODE,$RECOMMENDER_TEL_NO,$benefit,$email";
            $password = md5($pass);
            $stmt = $this->conn->prepare("INSERT INTO TB_CUST (cust_id, password, business_name, owner_name, addr_cont, tel_no,
              reg_date,addr_cd,INVITE_RECOMMENDER_CODE,RECOMMENDER_TEL_NO,benefit_yn,grade_class_cd,email,CTG_CD)
              VALUES (?, ?, ?, ?, ?, ?,now(),?,?,?,?,'egg0',?,?)");
            $stmt->bind_param("ssssssssssss", $cust_id, $password, $business_name, $owner_name, $addr_cont, $tel_no,
            $addr_cd,$INVITE_RECOMMENDER_CODE,$RECOMMENDER_TEL_NO,$benefit,$email,$ctg_cd);
            if ($stmt->execute()) {
                return USER_CREATED;
            } else {
                return USER_NOT_CREATED;
            }
        } else {
            return USER_ALREADY_EXIST;
        }
    }


    private function isUserExist($cust_id, $business_name, $tel_no)
    {
        $stmt = $this->conn->prepare("SELECT cust_id FROM TB_CUST WHERE cust_id = ? OR business_name = ? ");
        $stmt->bind_param("sss", $cust_id, $business_name, $tel_no);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function checkSeller($cust_id, $pass)
    {
        $stmt = $this->conn->prepare("SELECT sel.seller_id, cst.business_name, cst.addr_cont, cst.tel_no from TB_CUST cst join TB_SELLER sel on cst.cust_id = sel.seller_id where sel.seller_id = ? AND cst.password = ? AND cst.activ_yn = 1;");
        $password = md5($pass);
        $stmt->bind_param("ss", $cust_id, $password);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELLER_DENINED;
        }

    }

    public function in_reCd($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT cus.INVITE_RECOMMENDER_CODE,re.cust_id
          from TB_CUST cus left join  TB_CUST re on cus.INVITE_RECOMMENDER_CODE = re.RECOMMENDER_CODE
          where cus.cust_id = ?");
          // and re.INVITE_RECOMMENDER_CODE != ''
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function initial_order_count($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT his.order_no FROM TB_ORDER ord join TB_CUST_PAYMENT_HIS his
        on ord.order_no = his.order_no left join TB_ORDER_ITEM item on his.order_no = item.order_no
        WHERE his.cust_id = ?
        and (his.PAYMENT_HIS_CD='CP' or his.PAYMENT_HIS_CD='BW')
        and item.order_cond_cd = '03'");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function wpay_order_count($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT count(ord.order_no) FROM TB_ORDER ord
        join (SELECT * from TB_ORDER_ITEM group by ORDER_NO,seller_id) item on ord.ORDER_NO = item.ORDER_NO
        WHERE ord.cust_id = ? and ord.wtid like '%WPAY%' and item.order_cond_cd = '03' group by ord.cust_id");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function select_use_event_coupon($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT coupon_no FROM TB_COUPON WHERE cust_id=? and COUPON_CLASS_CD = 'AAA' and COUPON_USE_YN = '1'");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function reCd($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT RECOMMENDER_CODE from TB_CUST where cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

        public function re_code_select($code)
    {
      // echo "$code";
        $stmt = $this->conn->prepare("SELECT RECOMMENDER_CODE FROM TB_CUST where RECOMMENDER_CODE = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function re_code_update($code,$cust_id)
    {
        // echo "$code,$cust_id";
        $stmt = $this->conn->prepare("UPDATE TB_CUST set RECOMMENDER_CODE = ? where cust_id =?");
        $stmt->bind_param("ss", $code,$cust_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function re_code_coupon_select($coupon)
{
  // echo "$code";
    $stmt = $this->conn->prepare("SELECT COUPON_NO FROM TB_COUPON where COUPON_NO = ?");
    $stmt->bind_param("s", $coupon);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return $stmt;
    } else {
        return SELECT_FAILED;
    }
}

public function re_code_coupon_insert($coupon,$cust_id,$rit_name)
{
    if ($rit_name == "AAA" || $rit_name == "AAL") {
      $stmt = $this->conn->prepare("INSERT INTO
        TB_COUPON(COUPON_NO, COUPON_CLASS_CD, COUPON_REG_DATE, CUST_ID,COUPON_DEADLINE_TM)
      VALUES (?,'$rit_name',now(),?,'30');");
    }else {
      $stmt = $this->conn->prepare("INSERT INTO
        TB_COUPON(COUPON_NO, COUPON_CLASS_CD, COUPON_REG_DATE, CUST_ID)
      VALUES (?,'$rit_name',now(),?);");
    }
    // echo "$code,$cust_id";

    $stmt->bind_param("ss", $coupon,$cust_id);
    if ($stmt->execute()) {
        return INSERT_COMPLETED;
    } else {
        return INSERT_FAILED;
    }
}

public function re_code_coupon_insert_day($coupon,$cust_id,$rit_name,$day)
{
      $stmt = $this->conn->prepare("INSERT INTO
        TB_COUPON(COUPON_NO, COUPON_CLASS_CD, COUPON_REG_DATE, CUST_ID,COUPON_DEADLINE_TM)
      VALUES (?,'$rit_name',now(),?,?);");

    $stmt->bind_param("ssi", $coupon,$cust_id,$day);
    if ($stmt->execute()) {
        return INSERT_COMPLETED;
    } else {
        return INSERT_FAILED;
    }
}

public function selectCouponClassCd($coupon_code)
{
  // echo "검색성공";

  $str = "SELECT COUPON_MSG FROM TB_COUPON_CLASS_CD WHERE COUPON_CLASS_CD = '$coupon_code'";

  $stmt = $this->conn->prepare("$str");
  // $stmt = bind_param("s",$coupon_code);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
      return $stmt;
  } else {
      return SELECT_FAILED;
  }
}

    public function re_code_count($code)
    {
        // echo "$code";
        $stmt = $this->conn->prepare("SELECT cus.cust_id,count(ord.ORDER_NO) as no_count FROM TB_CUST cus
        left join (SELECT a.* from TB_ORDER a join (SELECT * from TB_ORDER_ITEM where order_cond_cd = '03') b on a.ORDER_NO = b.ORDER_NO group by a.order_no) ord
        on cus.cust_id = ord.cust_id WHERE cus.INVITE_RECOMMENDER_CODE = ?
        group by cus.CUST_ID HAVING no_count > 0");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }



    //Check user for login
    public function checkUser($cust_id, $pass)
    {
        $stmt = $this->conn->prepare("SELECT cust_id, business_name, addr_cont, tel_no ,DELIV_POSITION FROM TB_CUST WHERE cust_id = ? AND password = ? AND activ_yn = 1");
        $password = md5($pass);
        $stmt->bind_param("ss", $cust_id, $password);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return USER_DENINED;
        }

    }

    public function checkAdmin($admin_id, $pass)
    {
        $stmt = $this->conn->prepare("SELECT ADMIN_ID,PASSWORD,ADMIN_NAME,REG_DATE,ADMIN_TYPE FROM TB_ADMIN where admin_id = ? and password = ?");
        $password = md5($pass);
        $stmt->bind_param("ss", $admin_id, $password);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return USER_DENINED;
        }

    }



    public function UserAccount($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT cust_accn_no,deposit_bln FROM TB_CUST_PAYMENT WHERE cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return ACCOUNT_NOT_EXIST;
        }

    }

    public function UserAccountLimit($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT cust_accn_bn_name,IFNULL(CUST_DEPOSIT_ACCN_NO,cust_accn_no),deposit_bln,credit_limit,cust.BENEFIT_YN,cust.activ_yn
          ,pay.GUARANTEE_INSURANCE_SECURITIES,pay.BUSINESS_REGISTRATION,pay.CONTRACT,pay.SETTLEMENT_DT,pay.STATUS,cust.business_name
          FROM TB_CUST_PAYMENT pay join TB_CUST cust on pay.CUST_ID=cust.CUST_ID  WHERE pay.cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return ACCOUNT_NOT_EXIST;
        }

    }

    public function user_activ_yn_update($activ_yn,$cust_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST set  activ_yn = ? where cust_id = ?");
        $stmt->bind_param("is",$activ_yn,$cust_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function SellerList()
    {
        $stmt = $this->conn->prepare("SELECT seller_id, seller_name from TB_SELLER where activ_yn = 1");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELLER_NOT_EXIST;
        }

    }

    public function SellerList_All($search_textfield)
    {
      // echo "$search_textfield";
      if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
        $search_textfield_where = "";
      }else {
        $search_textfield_where = "and (sel.seller_name like concat('%',?,'%') or sel.seller_id like concat('%',?,'%'))";
      }
        $stmt = $this->conn->prepare("SELECT sel.seller_id,sel.seller_name,sel.addr_cd,sel.addr_cont,sel.tel_no,sel.activ_yn,sel.code_own_yn,sel.weekday,sel.weekend,sel.holiday,ifnull(sel_cust.count_cust,0),sel.SELLER_CONT from TB_SELLER sel left join (SELECT count(cust_id) as count_cust,seller_id
        from TB_SELLER_BY_CUST group by seller_id) sel_cust on sel.SELLER_ID=sel_cust.SELLER_ID where sel.activ_yn = 1 and sel.seller_id != 'orderhero' $search_textfield_where order by COUNT_cust  desc");
        if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
        }else {
          $stmt->bind_param("ss",$search_textfield,$search_textfield);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function UserList_All($search_textfield,$orderby,$admin_id,$admin_type,$none_cust,$s_point,$list,$sdg_get)
    {
      if (isset($s_point) && isset($list)) {
        $limit = "limit $s_point,$list";
      }else {
        $limit = "";
      }
      //정산관리 검색필터추가!
      if ($sdg_get == "basic") {
        $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
      }else if($sdg_get == "ALL"){
        $sdg_get_where = "";
      }else if($sdg_get == "sdg"){
        $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
      }else {
        $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
      }
      // echo "$search_textfield";
      if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
        $search_textfield_where = "where cust.activ_yn = 1";
        if ($none_cust == "" || !isset($none_cust) || empty($none_cust)) {
        }else {
          $search_textfield_where = "where cust.activ_yn = 0";
        }
      }else {
        $search_textfield_where = "where (cust.business_name like concat('%',?,'%') or cust.cust_id like concat('%',?,'%') or cust.owner_name like concat('%',?,'%')) and cust.activ_yn = 1";
        if ($none_cust == "" || !isset($none_cust) || empty($none_cust)) {
        }else {
          $search_textfield_where = "where (cust.business_name like concat('%',?,'%') or cust.cust_id like concat('%',?,'%') or cust.owner_name like concat('%',?,'%')) and cust.activ_yn = 0";
        }
      }


      if ($orderby == "upEvent") {
        $orderby_show = "order by cust_pay.DEPOSIT_BLN asc,cust.cust_id";
      }else if ($orderby == "downEvent") {
        $orderby_show = "order by cust_pay.DEPOSIT_BLN desc,cust.cust_id";
      }else{
        $orderby_show = "order by  cust.reg_date desc,cust.cust_id";
      }

      if ($admin_type == "MASTER" || $admin_type == "MANAGER" || $admin_type == "MD") {
        if ($admin_id == "" || !isset($admin_id) || empty($admin_id) || $admin_id == "ALL") {
          $where = "";
        }elseif ($admin_id == "NONE") {
          $where = " and (cust.admin_id = '' or cust.admin_id is null)";
        }else {
          $where = " and cust.admin_id = '$admin_id'";
        }
      }else {
        $where = " and cust.admin_id = '$admin_id'";
      }

        $stmt = $this->conn->prepare("SELECT cust.cust_id,cust.business_name,cust.owner_name,cust.addr_cd,cust.addr_cont,cust.tel_no,cust.activ_yn,
          if(isnull(sel_cust.count_cust),0,sel_cust.count_cust),cust_pay.DEPOSIT_BLN,cust_pay.CREDIT_LIMIT,cust.admin_id,gcd.grade_class_name,cust.DELIV_POSITION from (SELECT joincust.cust_id,joincust.password,joincust.business_name,joincust.owner_name,joincust.addr_cd,joincust.addr_cont,joincust.tel_no,
          joincust.activ_yn,joincust.ad_aggr_yn,joincust.reg_date,joincust.RECOMMENDER_TEL_NO,joincust.RECOMMENDER_CODE,joincust.admin_id,joincust.grade_class_cd,joincust.DELIV_POSITION from TB_CUST joincust
          left join TB_SELLER sel on joincust.cust_id = sel.seller_id where sel.seller_id is null) cust
          left join (SELECT count(seller_id) as count_cust,cust_id,seller_id from TB_SELLER_BY_CUST group by cust_id) sel_cust
          on cust.cust_id=sel_cust.cust_id join TB_CUST_PAYMENT cust_pay on cust.cust_id = cust_pay.cust_id
          join TB_GRADE_CLASS_CD gcd on cust.grade_class_cd = gcd.grade_class_cd
          $search_textfield_where $where $sdg_get_where $orderby_show $limit");
        if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
        }else {
          $stmt->bind_param("sss",$search_textfield,$search_textfield,$search_textfield);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function admin_sales_matching($admin_id,$cust_id){
      $stmt = $this->conn->prepare("UPDATE TB_CUST SET admin_id = ?  where cust_id = ?");//스테이트 먼트생성
      $stmt->bind_param("ss",$admin_id,$cust_id);//매개변수 가져옴
      if ($stmt->execute()) { // 값입력
        return UPDATE_COMPLETED;
      }else {
        return UPDATE_FAILED;
      }
    }



    public function SellerList_Matching($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT sel_cust.cust_id,sel.seller_id,sel.seller_name,sel_cust.MARGIN_RATE,sel_cust.min_order_pr from TB_SELLER as sel left join (SELECT * from TB_SELLER_BY_CUST WHERE CUST_ID = ?) sel_cust
        on sel.SELLER_ID = sel_cust.SELLER_ID where sel.activ_yn = 1 and sel.seller_id != 'orderhero' order by sel_cust.cust_id desc");
        //(sel.seller_id != 'deliverylab' and
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }
    public function Seller_CustList_Matching($seller_id,$search_textfield,$cust_id)
    {
      if (isset($cust_id) || $cust_id != "") {
        $cust_table = " (SELECT * from TB_CUST where cust_id = '$cust_id') ";
      }else {
        $cust_table = " TB_CUST ";
      }
          // echo "$seller_id,$search_textfield";
      if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
        $search_textfield_where = "";
      }else {
        $search_textfield_where = "and (cust.cust_id like concat('%',?,'%') or cust.business_name like concat('%',?,'%'))";
      }
        $stmt = $this->conn->prepare("SELECT sel_cust.cust_id,cust.cust_id,cust.business_name,sel_cust.MARGIN_RATE,sel_cust.staff_no,
          staf.tel_no,staf.staff_name,sel_cust.cust_cd from $cust_table cust
          left join TB_SELLER sel on cust.cust_id = sel.seller_id left join (SELECT * from TB_SELLER_BY_CUST WHERE seller_id=?) sel_cust
        on cust.cust_id = sel_cust.cust_id left join TB_STAFF staf on sel_cust.staff_no = staf.staff_no where sel.seller_id is null $search_textfield_where order by sel_cust.cust_id desc");
        //(sel.seller_id != 'deliverylab' and
        if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
            $stmt->bind_param("s",$seller_id);
        }else {
           $stmt->bind_param("sss",$seller_id,$search_textfield,$search_textfield);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }


    public function CategoryList()
    {
        $stmt = $this->conn->prepare("SELECT prd.first_class_cd,cls.class_name  from TB_PROD_BY_SELLER prd_sel join TB_PROD prd on prd_sel.prod_cd = prd.prod_cd join TB_CLASS_CD cls on prd.first_class_cd = cls.class_cd join TB_SELLER tb_sel on prd_sel.seller_id = tb_sel.seller_id where tb_sel.activ_yn = '1' group by 2 order by find_in_set(cls.class_name,'농산물,수산,축산물,가공품,잡화') asc");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELLER_NOT_EXIST;
        }

    }

    public function selectSearchCust($autocomplete1)
    {
      if($autocomplete1 == ""){
        $stmt = $this->conn->prepare("SELECT business_name from TB_CUST where business_name = 'notissetName'");
      }else{
        $stmt = $this->conn->prepare("SELECT cst.business_name from TB_CUST cst join TB_CUST_PAYMENT cst_id on cst.cust_id = cst_id.cust_id where cst.business_name like CONCAT ('%',?,'%') order by cst.business_name desc");
        $stmt->bind_param("s", $autocomplete1);
      }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // public function selectSearchProd($autocomplete2)
    // {
    //   if($autocomplete2 == ""){
    //     $stmt = $this->conn->prepare("SELECT prod_name,origin_name,prod_wgt,sale_unit,fact_name,prod_cd from TB_PROD where prod_name = 'notissetName'");
    //   }else{
    //     $stmt = $this->conn->prepare("SELECT prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name, prd.prod_cd from TB_PROD prd where prd.prod_name like  CONCAT('%', ?, '%') order by prd.prod_name asc");
    //     $stmt->bind_param("s", $autocomplete2);
    //   }
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }

    public function selectSearchProd($autocomplete2)
    {
      if($autocomplete2 == ""){
        $stmt = $this->conn->prepare("SELECT prod_name,prod_cont,prod_wgt,sale_unit,fact_name,prod_cd from TB_PROD where prod_name = 'notissetName'");
      }else{
        $stmt = $this->conn->prepare("SELECT prd.prod_name, prd.prod_cont, prd.prod_wgt, prd.sale_unit, prd.fact_name, prd.prod_cd from TB_PROD prd where prd.prod_name like  CONCAT('%', ?, '%') order by prd.prod_name asc");
        $stmt->bind_param("s", $autocomplete2);
      }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function insertNoticeAdmin($cust_id, $seller_id, $notice_title, $notice_main, $notice_cond_cd)
    {
      $stmt = $this->conn->prepare("INSERT INTO TB_NOTICE (cust_id, seller_id, notice_title, notice_main, notice_cond_cd, reg_date) values (?, ?, ?, ?, ?, now())");
      $stmt->bind_param("sssss", $cust_id, $seller_id, $notice_title, $notice_main, $notice_cond_cd);
      if ($stmt->execute()) {
          return INSERT_COMPLETED;
      } else {
          return INSERT_FAILED;
      }
    }

    public function deleteNoticAdmin($cust_id, $notice_no)
    {
        $stmt = $this->conn->prepare("DELETE from TB_NOTICE where cust_id = ? and notice_no = ?");
        $stmt->bind_param("si", $cust_id, $notice_no);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function updateNoticeModifyAdmin($notice_answer, $notice_no)
    {
        $stmt = $this->conn->prepare("UPDATE TB_NOTICE set  answer_unit = 1, notice_answer=?, notice_answer_Date  = now() where notice_no = ?");
        $stmt->bind_param("si", $notice_answer,  $notice_no);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }
    public function insertnoticeAns($notice_answer, $notice_no,$sel_id)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_NOTICE_ANS(notice_answer,notice_no,seller_id,reg_date) VALUES (?,?,?,now())");
        $stmt->bind_param("sis", $notice_answer, $notice_no,$sel_id);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function firstClass()
    {
        $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.first_class_cd = cls_cd.class_cd where prd.first_class_cd is not null");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }

    }

    public function secondClass()
    {
        $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd where prd.second_class_cd !=''");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }

    }
    public function secondClassCust($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd join TB_FAVOR_PROD fav on prd.prod_cd = fav.prod_cd where prd.second_class_cd !='' and fav.cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }

    }
    public function secondClassSeller($seller_Id)
    {
        $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd join TB_PROD_BY_SELLER sel on prd.prod_cd = sel.prod_cd where sel.seller_id = ?");
        $stmt->bind_param("s", $seller_Id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }

    }
    public function secondClassSeller2($class_cd)
    {
        $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd join TB_PROD_BY_SELLER sel on prd.prod_cd = sel.prod_cd where cls_cd.class_cd like concat('%',?,'%')");
        $stmt->bind_param("s", $class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }

    }


    public function productList($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name from TB_PROD prd left join (SELECT prod_cd from TB_FAVOR_PROD where cust_id = ?) fav on prd.prod_cd = fav.prod_cd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd where fav.prod_cd is null and prd_sel.sale_yn = 1 order by 1");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }

    }
    public function productListFavcd($cust_id,$seller_id_main)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, fav.prod_cd favcd,prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name ,concat(prd_sel.prod_cd,'_',prd_sel.seller_id)  prod_seller ,concat(fav.prod_cd,'_',fav.seller_id) fav_seller from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd  left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id = ?) fav on concat(prd_sel.prod_cd,'_',prd_sel.seller_id) = concat(fav.prod_cd,'_',fav.seller_id) join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where prd_sel.sale_yn = 1  and sel_activ.activ_yn = 1 and prd_sel.seller_id = ? order by 1,10");
        $stmt->bind_param("ss", $cust_id,$seller_id_main);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }
    public function productListFavcd2($cust_id,$class_cd)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, fav.prod_cd favcd,prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name,concat(prd_sel.prod_cd,'_',prd_sel.seller_id)  prod_seller ,concat(fav.prod_cd,'_',fav.seller_id) fav_seller ,sel_activ.seller_name,prd_sel.seller_id from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd  left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav on concat(prd_sel.prod_cd,'_',prd_sel.seller_id) = concat(fav.prod_cd,'_',fav.seller_id) join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where prd_sel.sale_yn = 1  and sel_activ.activ_yn = 1 and prd.FIRST_CLASS_CD like concat('%',?,'%') order by 1,10");
        $stmt->bind_param("ss", $cust_id,$class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }

    public function productListSearch($cust_id,$like1,$like2)
    {
        if($like1 ==""){
          $stmt = $this->conn->prepare("SELECT * from TB_PROD where prod_name=''");
        }else{
          $stmt = $this->conn->prepare("SELECT prd.prod_cd, fav.prod_cd favcd,prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price,prd.pict_file_name, prd.fact_name,concat(prd_sel.prod_cd,'_',prd_sel.seller_id)  prod_seller ,concat(fav.prod_cd,'_',fav.seller_id) fav_seller ,sel_activ.seller_name,prd_sel.seller_id from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd  left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav on concat(prd_sel.prod_cd,'_',prd_sel.seller_id) = concat(fav.prod_cd,'_',fav.seller_id) join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where prd_sel.sale_yn = 1  and sel_activ.activ_yn = 1 and prd.prod_name like concat('%',?,'%') order by  case   WHEN prd.prod_name LIKE ? THEN 0 ELSE 1 END ,prd_sel.prod_price , prd.prod_name asc ");
          $stmt->bind_param("sss", $cust_id,$like1,$like2);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }

    public function cookieprice($prod_cd,$sellerId)
    {
        $stmt = $this->conn->prepare("SELECT prod_price from TB_PROD_BY_SELLER where prod_cd= ? and seller_id = ?");
        $stmt->bind_param("ss",$prod_cd,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }




    public function insertCart($cust_id, $prod_cd, $prod_count,$seller_id)
    {
        $stmt = $this->conn->prepare("INSERT into TB_CART (cust_id, prod_cd, prod_count,seller_id) values(?, ?, ?, ?)");
        $stmt->bind_param("sidi", $cust_id, $prod_cd, $prod_count,$seller_id);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }

    }


    public function updateCart($cust_id, $prod_cd, $prod_count,$seller_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CART set prod_count = ? where cust_id = ? and prod_cd = ? and seller_id = ?");
        $stmt->bind_param("dsis", $prod_count, $cust_id, $prod_cd,$seller_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }

    }

    public function selectCart($cust_id, $prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT prod_count from TB_CART where cust_id = ? and prod_cd = ?");
        $stmt->bind_param("si", $cust_id, $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function cart($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT sum(prod_count) from TB_CART where cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function cartpay($cust_id)
    {
//       SELECT
// 	idx,
//     CASE
// 		WHEN type = '1'
// 		THEN '의사'
// 		WHEN type = '2'
// 		THEN '장군'
// 		WHEN type = '3'
// 		THEN '왕'
// 		ELSE '일반인'
// 	END AS hero_type,
// 	name
// FROM hero_collection;
// // 2021. 01. 14일 리팩토링
// //특가상품
// $event_prod ="A0310046,E0000000,E0000001,E0000002,E1000000";
//
//         $evetn_qury = "CASE
//                	WHEN cart.prod_cd = 'A0310046'
//                	THEN 59000
//                	WHEN cart.prod_cd = 'E0000000'
//                	THEN 2500
//                 WHEN cart.prod_cd = 'E0000001'
//                 THEN 900
//                 WHEN cart.prod_cd = 'E0000002'
//                 THEN 14990
//                 WHEN cart.prod_cd = 'E1000000'
//                 THEN 2000
//                END";
//
//        $evetn_sel_qury = "CASE
//                WHEN cart.prod_cd = 'A0310046'
//                THEN cart.seller_id = 'deliverylab'
//                WHEN cart.prod_cd = 'E0000000'
//                THEN cart.seller_id = 'deliverylab'
//                WHEN cart.prod_cd = 'E0000001'
//                THEN cart.seller_id = 'deliverylab'
//                WHEN cart.prod_cd = 'E0000002'
//                THEN cart.seller_id = 'deliverylab'
//                WHEN cart.prod_cd = 'E1000000'
//                THEN cart.prod_cd = 'E1000000'
//              END";
       //동원/한화
       // $evetn_sel_qury = "CASE
       //         WHEN cart.prod_cd = 'A0310046'
       //         THEN cart.seller_id = 'deliverylab'
       //         WHEN cart.prod_cd = 'E0000000'
       //         THEN cart.seller_id = '1018130747'
       //         WHEN cart.prod_cd = 'E0000001'
       //         THEN cart.seller_id = '3128125280'
       //         WHEN cart.prod_cd = 'E0000002'
       //         THEN cart.seller_id = 'deliverylab'
       //       END";



        $stmt = $this->conn->prepare("SELECT
sum(if(cart.prod_cd = discunt.prod_cd
  ,if(INSTR('$this->JinhyunPom',sel_pc.seller_id),if(pt.TAXFREE_YN = 0,round(sel_pp.SELLER_PROD_PRICE*1.1),sel_pp.SELLER_PROD_PRICE),
    if(INSTR('$this->event_prod',cart.prod_cd) and $this->evetn_sel_qury,$this->evetn_qury,if(pt.TAXFREE_YN = 0
  ,round(round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
  ,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))))
  ,if(INSTR('$this->JinhyunPom',sel_pc.seller_id),if(pt.TAXFREE_YN = 0,round(sel_pp.SELLER_PROD_PRICE*1.1),sel_pp.SELLER_PROD_PRICE),
    if(INSTR('$this->event_prod',cart.prod_cd) and $this->evetn_sel_qury,$this->evetn_qury,if(pt.TAXFREE_YN = 0
  ,round(round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
  ,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))*cart.prod_count) as price,sum(cart.prod_count)
from  (SELECT * from TB_CART where cust_id = ?) cart join TB_PROD pt
on cart.prod_cd = pt.prod_cd join TB_SELLER sel on
cart.seller_id = sel.seller_id join TB_SELLER_PROD_CD sel_pc
on concat(cart.prod_cd,'_',cart.seller_id) = concat(sel_pc.prod_cd,'_',sel_pc.seller_id)
 join TB_SELLER_PROD_PRICE sel_pp
on concat(sel_pc.seller_prod_cd,'_',sel_pc.seller_id) = concat(sel_pp.seller_prod_cd,'_',sel_pp.seller_id)
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?)
selcust on cart.seller_id = selcust.seller_id
left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?)
 discunt on cart.seller_id=discunt.seller_id and cart.prod_cd=discunt.prod_cd");
 // concat(cart.seller_id,'_',cart.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)");
        $stmt->bind_param("sss", $cust_id,$cust_id,$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function cartpay_seller($cust_id,$seller_id)
    {
      // //특가상품
      // $event_prod ="A0310046,E0000000,E0000001,E0000002,E1000000";
      //
      //         $evetn_qury = "CASE
      //                	WHEN cart.prod_cd = 'A0310046'
      //                	THEN 59000
      //                	WHEN cart.prod_cd = 'E0000000'
      //                	THEN 2500
      //                 WHEN cart.prod_cd = 'E0000001'
      //                 THEN 900
      //                 WHEN cart.prod_cd = 'E0000002'
      //                 THEN 14990
      //                 WHEN cart.prod_cd = 'E1000000'
      //                 THEN 2000
      //                END";
      //
      //        $evetn_sel_qury = "CASE
      //                WHEN cart.prod_cd = 'A0310046'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E0000000'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E0000001'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E0000002'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E1000000'
      //                THEN cart.prod_cd = 'E1000000'
      //              END";
             //동원/한화
             // $evetn_sel_qury = "CASE
             //         WHEN cart.prod_cd = 'A0310046'
             //         THEN cart.seller_id = 'deliverylab'
             //         WHEN cart.prod_cd = 'E0000000'
             //         THEN cart.seller_id = '1018130747'
             //         WHEN cart.prod_cd = 'E0000001'
             //         THEN cart.seller_id = '3128125280'
             //         WHEN cart.prod_cd = 'E0000002'
             //         THEN cart.seller_id = 'deliverylab'
             //       END";



        $stmt = $this->conn->prepare("SELECT
sum(if(cart.prod_cd = discunt.prod_cd
  ,if(INSTR('$this->JinhyunPom',sel_pc.seller_id),if(pt.TAXFREE_YN = 0,round(sel_pp.SELLER_PROD_PRICE*1.1),sel_pp.SELLER_PROD_PRICE),
    if(INSTR('$this->event_prod',cart.prod_cd) and $this->evetn_sel_qury,$this->evetn_qury,if(pt.TAXFREE_YN = 0
  ,round(round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
  ,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))))
  ,if(INSTR('$this->JinhyunPom',sel_pc.seller_id),if(pt.TAXFREE_YN = 0,round(sel_pp.SELLER_PROD_PRICE*1.1),sel_pp.SELLER_PROD_PRICE),
    if(INSTR('$this->event_prod',cart.prod_cd) and $this->evetn_sel_qury,$this->evetn_qury,if(pt.TAXFREE_YN = 0
  ,round(round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
  ,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))*cart.prod_count) as price,sum(cart.prod_count)
from  (SELECT * from TB_CART where cust_id = ? and seller_id = '$seller_id') cart join TB_PROD pt
on cart.prod_cd = pt.prod_cd join TB_SELLER sel on
cart.seller_id = sel.seller_id join TB_SELLER_PROD_CD sel_pc
on concat(cart.prod_cd,'_',cart.seller_id) = concat(sel_pc.prod_cd,'_',sel_pc.seller_id)
 join TB_SELLER_PROD_PRICE sel_pp
 /*join (SELECT * from TB_SELLER_PROD_PRICE where ORDER_DEADLINE_TM = 2) sel_pp*/
on concat(sel_pc.seller_prod_cd,'_',sel_pc.seller_id) = concat(sel_pp.seller_prod_cd,'_',sel_pp.seller_id)
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?)
selcust on cart.seller_id = selcust.seller_id
left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?)
 discunt on cart.seller_id=discunt.seller_id and cart.prod_cd=discunt.prod_cd");
 // concat(cart.seller_id,'_',cart.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)");
        $stmt->bind_param("sss", $cust_id,$cust_id,$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function cartpay_sellerTM($cust_id,$seller_id,$tm)
    {
      // //특가상품
      // $event_prod ="A0310046,E0000000,E0000001,E0000002,E1000000";
      //
      //         $evetn_qury = "CASE
      //                	WHEN cart.prod_cd = 'A0310046'
      //                	THEN 59000
      //                	WHEN cart.prod_cd = 'E0000000'
      //                	THEN 2500
      //                 WHEN cart.prod_cd = 'E0000001'
      //                 THEN 900
      //                 WHEN cart.prod_cd = 'E0000002'
      //                 THEN 14990
      //                 WHEN cart.prod_cd = 'E1000000'
      //                 THEN 2000
      //                END";
      //
      //        $evetn_sel_qury = "CASE
      //                WHEN cart.prod_cd = 'A0310046'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E0000000'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E0000001'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E0000002'
      //                THEN cart.seller_id = 'deliverylab'
      //                WHEN cart.prod_cd = 'E1000000'
      //                THEN cart.prod_cd = 'E1000000'
      //              END";
             //동원/한화
             // $evetn_sel_qury = "CASE
             //         WHEN cart.prod_cd = 'A0310046'
             //         THEN cart.seller_id = 'deliverylab'
             //         WHEN cart.prod_cd = 'E0000000'
             //         THEN cart.seller_id = '1018130747'
             //         WHEN cart.prod_cd = 'E0000001'
             //         THEN cart.seller_id = '3128125280'
             //         WHEN cart.prod_cd = 'E0000002'
             //         THEN cart.seller_id = 'deliverylab'
             //       END";
        $stmt = $this->conn->prepare("SELECT
sum(if(cart.prod_cd = discunt.prod_cd
  ,if(INSTR('$this->JinhyunPom',sel_pc.seller_id),if(pt.TAXFREE_YN = 0,round(sel_pp.SELLER_PROD_PRICE*1.1),sel_pp.SELLER_PROD_PRICE),
    if(INSTR('$this->event_prod',cart.prod_cd) and $this->evetn_sel_qury,$this->evetn_qury,if(pt.TAXFREE_YN = 0
  ,round(round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
  ,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))))
  ,if(INSTR('$this->JinhyunPom',sel_pc.seller_id),if(pt.TAXFREE_YN = 0,round(sel_pp.SELLER_PROD_PRICE*1.1),sel_pp.SELLER_PROD_PRICE),
    if(INSTR('$this->event_prod',cart.prod_cd) and $this->evetn_sel_qury,$this->evetn_qury,if(pt.TAXFREE_YN = 0
  ,round(round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
  ,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))*cart.prod_count) as price,sum(cart.prod_count)
from  (SELECT * from TB_CART where cust_id = ? and seller_id = '$seller_id') cart join TB_PROD pt
on cart.prod_cd = pt.prod_cd join TB_SELLER sel on
cart.seller_id = sel.seller_id join TB_SELLER_PROD_CD sel_pc
on concat(cart.prod_cd,'_',cart.seller_id) = concat(sel_pc.prod_cd,'_',sel_pc.seller_id)
 join (SELECT * from TB_SELLER_PROD_PRICE where ORDER_DEADLINE_TM = ?) sel_pp
on concat(sel_pc.seller_prod_cd,'_',sel_pc.seller_id) = concat(sel_pp.seller_prod_cd,'_',sel_pp.seller_id)
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?)
selcust on cart.seller_id = selcust.seller_id
left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?)
 discunt on cart.seller_id=discunt.seller_id and cart.prod_cd=discunt.prod_cd");
 // concat(cart.seller_id,'_',cart.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)");
        $stmt->bind_param("siss", $cust_id,$tm,$cust_id,$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function cartList($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT(prd_sel.seller_id) ,prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, crt.prod_count, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name ,sel_activ.seller_name,sel_activ.tel_no from TB_PROD prd join TB_CART crt on prd.prod_cd = crt.prod_cd join TB_PROD_BY_SELLER prd_sel on concat(crt.prod_cd,'_',crt.seller_id) = concat(prd_sel.prod_cd,'_',prd_sel.seller_id) join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where crt.cust_id = ? and prd_sel.sale_yn = 1  and sel_activ.activ_yn = 1");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CART_NOT_EXIST;
        }
    }

    public function insertOrder($cust_id, $memo,$wtid)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_ORDER (order_date, cust_id, reg_date, memo,wtid) values (now(), ?, now(), ?, ? )");
        $stmt->bind_param("sss", $cust_id, $memo,$wtid);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function selectOrder($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT order_no,admin.ADMIN_TEL_NO from TB_ORDER ord
          join TB_CUST cust on ord.CUST_ID = cust.CUST_ID left join TB_ADMIN admin
          on cust.ADMIN_ID = admin.ADMIN_ID where ord.cust_id = ?
          order by ord.order_date desc,ord.order_no desc limit 1");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectOrderBD($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT order_no,admin.ADMIN_TEL_NO from TB_ORDER ord
          join TB_CUST cust on ord.CUST_ID = cust.CUST_ID left join TB_ADMIN admin
          on cust.ADMIN_ID = admin.ADMIN_ID where ord.cust_id = ? and ord.ORDER_COND_CD = '06'
          order by ord.order_date desc,ord.order_no desc limit 1");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderSellerPay($order_no)
    {
        $stmt = $this->conn->prepare("SELECT ord.seller_id , sum(sel.prod_price * ord.prod_order_cnt ) as payment_pr from TB_ORDER_ITEM ord  join TB_SELLER ts on ts.seller_id = ord.seller_id join TB_PROD_BY_SELLER sel on concat(sel.prod_cd,'_',sel.seller_id)=concat(ord.prod_cd,'_',ord.seller_id) where order_no = ? group by 1");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }



    public function insertOrderItem($order_no,$sellerId,$prod_cd, $prod_order_cnt,$order_pay)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_ORDER_ITEM (order_no, seller_id, prod_cd, prod_order_cnt,order_pay) values (?, ?, ?, ?,?)");
        $stmt->bind_param("isidi",$order_no,$sellerId, $prod_cd, $prod_order_cnt,$order_pay);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function selectOrderItem($prod_cd,$sellerId)
    {
        $stmt = $this->conn->prepare("SELECT prod_price from  TB_PROD_BY_SELLER where prod_cd = ? and seller_id = ?");
        $stmt->bind_param("ss", $prod_cd,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateBalance($deposit_bln, $cust_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT set deposit_bln = ? where cust_id = ?");
        $stmt->bind_param("is", $deposit_bln, $cust_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }

    }

    public function updateimg($cust_id,$insurance_img,$img_name)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT set $insurance_img = ? where cust_id = ?");
        $stmt->bind_param("ss", $img_name, $cust_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function updateimg_site($cust_id,$insurance_img,$img_name)
    {
        $stmt = $this->conn->prepare("UPDATE TB_SITE SET $insurance_img = ?  WHERE cust_id = ?");
        $stmt->bind_param("ss", $img_name, $cust_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function updatecont_site($cust_id,$site_cont)
    {
        $stmt = $this->conn->prepare("UPDATE TB_SITE SET site_cont = ?  WHERE cust_id = ?");
        $stmt->bind_param("ss", $site_cont,$cust_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function insert_site($cust_id,$site_cont)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_SITE(CUST_ID,SITE_CONT) VALUES (?,?)");
        $stmt->bind_param("ss",$cust_id,$site_cont);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }


    public function insertCancelHistory($cust_id, $order_no, $payment_pr, $deposit_bln,$sellerId,$pay_his_cd,$his_memo,$auto_ID)
                 // insertCancelHistory($cust_id,$order_no,$insert_deposi_select,$final_bln,"orderhero","자동입금","$cust_id");
    {
        if (isset($auto_ID) && $auto_ID !== "") {
          $DEPOSIT_ID = $auto_ID;
        }elseif ($pay_his_cd == "BD") {//입금일//자동입금일
          $DEPOSIT_ID = (empty($_SESSION['admin_id'])) ? '' : $_SESSION['admin_id'];
        }else {
          $DEPOSIT_ID = "";
        }
        if (isset($his_memo)) {
        $stmt = $this->conn->prepare("INSERT INTO TB_CUST_PAYMENT_HIS (cust_id, order_no, payment_his_cd, payment_pr, deposit_bln, payment_date,seller_id,PAYMENT_HIS_MEMO,DEPOSIT_ID) values (?, ?, '$pay_his_cd', ?, ?, now(),?,'$his_memo','$DEPOSIT_ID')");
          // code...
        }else{
        $stmt = $this->conn->prepare("INSERT INTO TB_CUST_PAYMENT_HIS (cust_id, order_no, payment_his_cd, payment_pr, deposit_bln, payment_date,seller_id,DEPOSIT_ID) values (?, ?, '$pay_his_cd', ?, ?, now(),?,'$DEPOSIT_ID')");
        }
        $stmt->bind_param("siiis", $cust_id, $order_no, $payment_pr, $deposit_bln,$sellerId);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function updateBalanceHis($deposit_bln,$cust_id, $order_no,$seller_id,$befor_cd)
    {
      if ($befor_cd =="CP") {
        $prepare ="UPDATE TB_CUST_PAYMENT_HIS set payment_his_cd = 'CC', deposit_bln = ?,payment_date = now() where cust_id = ? and order_no = ? and seller_id =? and payment_his_cd = 'CR'";
      }elseif ($befor_cd =="VW") {
        $prepare ="UPDATE TB_CUST_PAYMENT_HIS set payment_his_cd = 'VC', deposit_bln = ?,payment_date = now() where cust_id = ? and order_no = ? and seller_id =? and payment_his_cd = 'CR'";
      }else {
        $prepare ="UPDATE TB_CUST_PAYMENT_HIS set payment_his_cd = 'BC', deposit_bln = ?,payment_date = now() where cust_id = ? and order_no = ? and seller_id =? and payment_his_cd = 'CR'";
      }

        $stmt = $this->conn->prepare($prepare);
        $stmt->bind_param("isis",$deposit_bln, $cust_id, $order_no,$seller_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function selectBalanceHiscd($order_no,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT payment_his_cd from TB_CUST_PAYMENT_HIS  where order_no  = ? and  seller_id = ? and cancel_yn = '1'");
        $stmt->bind_param("is", $order_no,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateHisCR($cust_id,$order_no,$seller_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT_HIS set cancel_yn = 1 where cust_id = ? and order_no = ? and seller_id = ? and (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW')");
        $stmt->bind_param("sis",$cust_id, $order_no, $seller_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function updateHisCRCancel($cust_id,$order_no,$seller_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT_HIS set cancel_yn = 0 where cust_id = ? and order_no = ? and seller_id = ? and (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW')");
        $stmt->bind_param("sis",$cust_id, $order_no, $seller_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function deleteCRhis($cust_id,$order_no,$seller_id)
    {
        $stmt = $this->conn->prepare("DELETE from TB_CUST_PAYMENT_HIS where payment_his_cd = 'CR' and cust_id = ? and order_no = ? and seller_id = ?");
        $stmt->bind_param("sis", $cust_id,$order_no,$seller_id);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function deleteCart($cust_id)
    {
        $stmt = $this->conn->prepare("DELETE from TB_CART where cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }
    public function deleteCartWhere($cust_id,$prod_cd,$sellerId)
    {//20.07.15이후쓰지않음
        $stmt = $this->conn->prepare("DELETE from TB_CART where cust_id = ? and prod_cd = ? and seller_id =?");
        $stmt->bind_param("sss", $cust_id,$prod_cd,$sellerId);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function selectOrderTrack($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03') group by 1 order by 1 desc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderTrackWithDays($cust_id, $days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03') and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderTrackWithMonth($cust_id, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03') and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderTrackWithWhere($cust_id, $where)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ? group by 1 order by 1 desc");
        $stmt->bind_param("ss", $cust_id, $where);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderTrackWithWhereAndDays($cust_id,$where,$days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("ssi", $cust_id, $where, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderTrackWithWhereAndMonth($cust_id,$where, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ? and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("ssi", $cust_id, $where, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderDetail($order_no,$sellerId)//alert창
    {
        $stmt = $this->conn->prepare("SELECT order_cond_cd from TB_ORDER_ITEM  where order_no = ? and seller_id = ? group by order_no,seller_id");
        $stmt->bind_param("is", $order_no,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderDetailMemo($order_no,$sel)
    {
        $stmt = $this->conn->prepare("SELECT memo from TB_CUST_MEMO where order_no = ? and seller_id = ?");
        $stmt->bind_param("is", $order_no,$sel);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderDetailAdminMemo($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT NO,ADMIN_ID,CUST_ID,URL,MEMO,REG_DATE FROM TB_MANAGER_MEMO_HIS WHERE cust_id = '$cust_id' order by no desc limit 1");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function insertOrderDetailAdminMemo($admin_id,$cust_id,$url,$memo)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_MANAGER_MEMO_HIS(ADMIN_ID,CUST_ID,URL,MEMO,REG_DATE) VALUES ('$admin_id','$cust_id','$url','$memo',now())");
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function selectDepositHis($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT pay.payment_date, pay_cd.payment_his_name, pay.payment_pr, pay.deposit_bln,sel.seller_name,pay.memo,sel.seller_id from TB_CUST_PAYMENT_HIS pay join TB_PAYMENT_HIS_CD pay_cd on pay.payment_his_cd = pay_cd.payment_his_cd join TB_SELLER sel on pay.seller_id = sel.seller_id where pay.cust_id = ? order by 1 desc,pay.no desc,4 asc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectDepositHisWithDays($cust_id, $days)
    {
        $stmt = $this->conn->prepare("SELECT pay.payment_date, pay_cd.payment_his_name, pay.payment_pr, pay.deposit_bln,sel.seller_name,pay.memo,sel.seller_id from TB_CUST_PAYMENT_HIS pay join TB_PAYMENT_HIS_CD pay_cd on pay.payment_his_cd = pay_cd.payment_his_cd join TB_SELLER sel on pay.seller_id = sel.seller_id  where pay.cust_id = ?  and date(pay.payment_date) >= date(subdate(now(), interval ? day)) order by 1 desc,pay.no desc,4 asc");
        $stmt->bind_param("si", $cust_id, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectDepositHisWithMonth($cust_id, $month)
    {
        $stmt = $this->conn->prepare("SELECT pay.payment_date, pay_cd.payment_his_name, pay.payment_pr, pay.deposit_bln,sel.seller_name,pay.memo,sel.seller_id from TB_CUST_PAYMENT_HIS pay join TB_PAYMENT_HIS_CD pay_cd on pay.payment_his_cd = pay_cd.payment_his_cd join TB_SELLER sel on pay.seller_id = sel.seller_id  where pay.cust_id = ? and date(pay.payment_date) >= date(subdate(now(), interval ? month)) order by 1 desc,pay.no desc,4 asc");
        $stmt->bind_param("si", $cust_id, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function insertFavorite($cust_id,$prod_cd,$sellerId)
    {
        // console.log("ㅎㅇ");
        $stmt = $this->conn->prepare("INSERT INTO TB_FAVOR_PROD (cust_id, prod_cd,seller_id) values (?,?,?)");
        $stmt->bind_param("sss", $cust_id, $prod_cd,$sellerId);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function selectAutoFavProdCd($sellerId,$seller_prod_cd)
    {
        // console.log("ㅎㅇ");
        $stmt = $this->conn->prepare("SELECT cd.PROD_CD,prd.prod_name FROM TB_SELLER_PROD_CD cd join TB_PROD prd on cd.PROD_CD = prd.PROD_CD WHERE SELLER_ID = ? and SELLER_PROD_CD = ?");
        $stmt->bind_param("ss", $sellerId,$seller_prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function deleteFavorite($cust_id, $prod_cd,$sellerId)
    {
        $stmt = $this->conn->prepare("DELETE from TB_FAVOR_PROD where cust_id = ? and prod_cd = ? and seller_id = ?");
        $stmt->bind_param("sis", $cust_id, $prod_cd,$sellerId);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function selectFavoriteList($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name,prd_sel.seller_id ,concat(prd.prod_cd,'_',prd_sel.seller_id)  prod_seller,sel_activ.seller_name from TB_PROD prd join TB_FAVOR_PROD fav on prd.prod_cd = fav.prod_cd join TB_PROD_BY_SELLER prd_sel on concat(fav.prod_cd,'_',fav.seller_id)=concat(prd_sel.prod_cd,'_',prd_sel.seller_id) join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where fav.cust_id = ? and prd_sel.sale_yn = 1 and sel_activ.activ_yn = 1 order by 1,10");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectFavoriteListWithWhere($cust_id, $secondclass)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name,prd_sel.seller_id ,concat(prd.prod_cd,'_',prd_sel.seller_id)  prod_seller ,sel_activ.seller_name from TB_PROD prd join TB_FAVOR_PROD fav on prd.prod_cd = fav.prod_cd join TB_PROD_BY_SELLER prd_sel on concat(fav.prod_cd,'_',fav.seller_id)=concat(prd_sel.prod_cd,'_',prd_sel.seller_id) join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd  join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where fav.cust_id = ? and cls_cd.class_name = ? and prd_sel.sale_yn = 1 and sel_activ.activ_yn = 1 order by 1,10");
        $stmt->bind_param("ss", $cust_id, $secondclass);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectProductListWithWhere($cust_id, $secondclass)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name from TB_PROD prd left join (SELECT prod_cd from TB_FAVOR_PROD where cust_id = ?) fav on prd.prod_cd = fav.prod_cd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd where fav.prod_cd is null and cls_cd.class_name = ? and prd_sel.sale_yn = 1 order by 1");
        $stmt->bind_param("ss", $cust_id, $secondclass);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectProductListWithWhereFavcd($cust_id, $secondclass,$seller_id_main)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd,fav.prod_cd favcd,prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name ,concat(prd_sel.prod_cd,'_',prd_sel.seller_id)  prod_seller ,concat(fav.prod_cd,'_',fav.seller_id) fav_seller from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id = ?) fav on concat(prd_sel.prod_cd,'_',prd_sel.seller_id) = concat(fav.prod_cd,'_',fav.seller_id) join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd  join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where cls_cd.class_name = ? and prd_sel.sale_yn = 1  and sel_activ.activ_yn = 1 and prd_sel.seller_id = ? order by 1,10");
        $stmt->bind_param("sss", $cust_id, $secondclass,$seller_id_main);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectProductListWithWhereFavcd2($cust_id, $secondclass,$seller_id_main)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, fav.prod_cd favcd,prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name,concat(prd_sel.prod_cd,'_',prd_sel.seller_id)  prod_seller ,concat(fav.prod_cd,'_',fav.seller_id) fav_seller ,sel_activ.seller_name,prd_sel.seller_id from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd  left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav on concat(prd_sel.prod_cd,'_',prd_sel.seller_id) = concat(fav.prod_cd,'_',fav.seller_id) join TB_CLASS_CD cls_cd on prd.second_class_cd = cls_cd.class_cd join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where cls_cd.class_name = ? and prd_sel.sale_yn = 1  and sel_activ.activ_yn = 1 and prd.FIRST_CLASS_CD like concat('%',?,'%') order by 1,10");
        $stmt->bind_param("sss", $cust_id, $secondclass,$seller_id_main);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }


    public function selectCancelReturn($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum,
          ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord
          join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum
          from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC' group by order_no) as pay
          on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no
          where ord.cust_id = ? and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08')
          group by 1 order by 1 desc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCancelReturnWithDays($cust_id, $days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,
          group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd
          join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS
          where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC' group by order_no) as pay
          on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no
          where ord.cust_id = ? and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08')
          and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCancelReturnWithMonth($cust_id, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,
          group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd
          on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum
          from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC' group by order_no) as pay
          on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no
          where ord.cust_id = ? and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08')
          and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCancel($cust_id,$where)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ?  group by 1 order by 1 desc");
        $stmt->bind_param("ss", $cust_id,$where);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCancelWithDays($cust_id,$where,$days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ?  and item.order_cond_cd = ?  and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("sis", $cust_id,$where,$days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCancelWithMonth($cust_id,$where, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, pay.payment_sum, ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ?  and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("sis", $cust_id,$where, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectReturn($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT ord.order_no, ord.order_date, pay.payment_pr, ord_cd.order_cond_name from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_CUST_PAYMENT_HIS pay on pay.order_no = ord.order_no where ord.cust_id = ? and ord.order_cond_cd = '05' order by 1 desc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectReturnWithDays($cust_id, $days)
    {
        $stmt = $this->conn->prepare("SELECT ord.order_no, ord.order_date, pay.payment_pr, ord_cd.order_cond_name from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_CUST_PAYMENT_HIS pay on pay.order_no = ord.order_no where ord.cust_id = ? and ord.order_cond_cd = '05' and date(ord.order_date) >= date(subdate(now(), interval ? day)) order by 1 desc");
        $stmt->bind_param("si", $cust_id, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectReturnWithMonth($cust_id, $month)
    {
        $stmt = $this->conn->prepare("SELECT ord.order_no, ord.order_date, pay.payment_pr, ord_cd.order_cond_name from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_CUST_PAYMENT_HIS pay on pay.order_no = ord.order_no where ord.cust_id = ? and ord.order_cond_cd = '05' and date(ord.order_date) >= date(subdate(now(), interval ? month)) order by 1 desc");
        $stmt->bind_param("si", $cust_id, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectProductDetail($prod_cd,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd_sel.prod_price, prd.pict_file_name, prd.fact_name, stn.stn_cond_name, prd_sel.max_purchase_am, sel.seller_name ,concat(prd.prod_cd,'_',prd_sel.seller_id)  prod_seller from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd join TB_STN_COND stn on prd.stn_cond_cd = stn.stn_cond_cd join TB_SELLER sel on prd_sel.seller_id = sel.seller_id  join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id where prd.prod_cd = ? and sel_activ.activ_yn = 1 and prd_sel.seller_id=?");
        $stmt->bind_param("ii", $prod_cd,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectFavoriteProduct($cust_id, $prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT concat(prod_cd,'_',seller_id)  prod_seller from TB_FAVOR_PROD where cust_id = ? and prod_cd = ?");
        $stmt->bind_param("si", $cust_id, $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectOrderTrackAdmin($seller_id_sub,$seller_id,$select_type,$search_textfield)
    {
        if($select_type == '전체'){
          if(!isset($search_textfield) || $search_textfield == ""){
            $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ? group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("ss", $seller_id_sub,$seller_id);
          }else{
            $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ? and cst.business_name like CONCAT('%',?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("sss", $seller_id_sub,$seller_id,$search_textfield);
          }
        }else{
          if(!isset($search_textfield) || $search_textfield == ""){
            $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ? and ocn.order_cond_name = ? group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("sss", $seller_id_sub,$seller_id , $select_type);
          }else{
            $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ? and ocn.order_cond_name = ? and cst.business_name like CONCAT('%', ?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("ssss", $seller_id_sub,$seller_id , $select_type,$search_textfield);
          }
        }

        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }


    public function selectOrderTrackAdminWithDays($seller_id_sub,$seller_id,$select_type_day,$select_type,$search_textfield)
    {
      if($select_type == '전체'){
        if(!isset($search_textfield) || $search_textfield == ""){
          $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ?  and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssi", $seller_id_sub,$seller_id, $select_type_day);
        }else{
          $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) and cst.business_name like CONCAT('%',?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssis", $seller_id_sub,$seller_id, $select_type_day,$search_textfield);
        }
      }else{
        if(!isset($search_textfield) || $search_textfield == ""){
          $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ?  and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ocn.order_cond_name = ? group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssis", $seller_id_sub,$seller_id, $select_type_day , $select_type);
        }else{
          $stmt = $this->conn->prepare("SELECT pay.order_no,pay.payment_date,pay.payment_pr,ocn.order_cond_name,cst.business_name from TB_CUST_PAYMENT_HIS pay join (select * from TB_ORDER_ITEM where seller_id = ? group by order_no) item on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where pay.seller_id  = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ocn.order_cond_name = ? and cst.business_name like CONCAT('%', ?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssiss", $seller_id_sub,$seller_id, $select_type_day , $select_type,$search_textfield);
        }
      }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCustomerListAdmin($seller_id, $select_type_customer)
    {
      if($select_type_customer == 1) {
        $stmt = $this->conn->prepare(
          "SELECT DISTINCT(cst.cust_id), cst.business_name, cst.owner_name, cst.addr_cont, cst.tel_no from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no join TB_CUST cst on ord.cust_id = cst.cust_id where ordi.seller_id = ? and cst.activ_yn = 1 order by cst.business_name desc");
        $stmt->bind_param("s", $seller_id);
      } else {
        $stmt = $this->conn->prepare(
          "SELECT DISTINCT(cst.cust_id), cst.business_name, cst.owner_name, cst.addr_cont, cst.tel_no
          from TB_ORDER ord
          join TB_ORDER_COND_CD ord_cd
          on ord.order_cond_cd = ord_cd.order_cond_cd
          join TB_ORDER_ITEM ordi
          on ord.order_no = ordi.order_no
          join TB_CUST cst
          on ord.cust_id = cst.cust_id
          where ordi.seller_id = ?
          and cst.activ_yn = 1
          order by cst.business_name asc");
        $stmt->bind_param("s", $seller_id);
      }

        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function countShippingAdmin($seller_id)
    {
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)) from TB_ORDER ord join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no where ordi.seller_id = ? and ordi.order_cond_cd = '02'");
        $stmt->bind_param("s", $seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function countBeforeShippingAdmin($seller_id)
    {
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)) from TB_ORDER ord join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no where ordi.seller_id = ? and ordi.order_cond_cd = '01'");
        $stmt->bind_param("s", $seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function checkOrderDetailAdmin($order_no,$sellerId)
    {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name from TB_ORDER_ITEM ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd where order_no = ? and ord.seller_id = ? group by 1");
        $stmt->bind_param("is", $order_no,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectOrderSeller($order_no,$order_nos)
    {
        $stmt = $this->conn->prepare("SELECT item.seller_id ,sname.seller_name,item.order_no ,od.order_cond_name,pay.payment_pr from TB_ORDER_ITEM item join TB_SELLER sname on item.seller_id = sname.seller_id  join TB_ORDER_COND_CD od on item.order_cond_cd = od.order_cond_cd join TB_PROD_BY_SELLER sel on concat(sel.prod_cd,'_',sel.seller_id)=concat(item.prod_cd,'_',item.seller_id) join (select order_no,payment_pr,seller_id from TB_CUST_PAYMENT_HIS where order_no= ? )  pay on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) where item.order_no = ? and pay.payment_pr>0 and order_cond_name = '출고전' or order_cond_name = '배송중' or order_cond_name = '배송완료' group by 1");
        $stmt->bind_param("ii", $order_no,$order_nos);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderSellerCancel($order_no,$order_nos)
    {
        $stmt = $this->conn->prepare("SELECT item.seller_id ,sname.seller_name,item.order_no ,od.order_cond_name,pay.payment_pr from TB_ORDER_ITEM item join TB_SELLER sname on item.seller_id = sname.seller_id  join TB_ORDER_COND_CD od on item.order_cond_cd = od.order_cond_cd join TB_PROD_BY_SELLER sel on concat(sel.prod_cd,'_',sel.seller_id)=concat(item.prod_cd,'_',item.seller_id) join (select order_no,payment_pr,seller_id  from TB_CUST_PAYMENT_HIS where order_no=?)  pay on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id) where item.order_no = ? and pay.payment_pr<0 and order_cond_name = '취소접수' or order_cond_name = '반품완료' group by 1");
        $stmt->bind_param("ii", $order_no,$order_nos);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderDetailCustomerAdmin($order_no_sub,$sellerId_sub,$order_no,$sellerId,$cond_name)
    {
      if($cond_name == '반품완료'){
        $stmt = $this->conn->prepare("SELECT cst.cust_id, cst.business_name, cst.owner_name, cst.addr_cont, cst.tel_no, ord.order_date, pay.payment_sum from TB_ORDER ord join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no join TB_SELLER_PROD_CD sel on concat(sel.prod_cd,'_',sel.seller_id)=concat(ordi.prod_cd,'_',ordi.seller_id) join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' and order_no = ? and seller_id = ?  group by order_no) as pay where ord.order_no = ? and ordi.seller_id = ? group by 1");
        $stmt->bind_param("isis", $order_no_sub,$sellerId_sub,$order_no,$sellerId);
      }else{
        $stmt = $this->conn->prepare("SELECT cst.cust_id, cst.business_name, cst.owner_name, cst.addr_cont, cst.tel_no, ord.order_date, pay.payment_sum from TB_ORDER ord join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no join TB_SELLER_PROD_CD sel on concat(sel.prod_cd,'_',sel.seller_id)=concat(ordi.prod_cd,'_',ordi.seller_id) join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'SI' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and order_no = ? and seller_id = ?  group by order_no) as pay where ord.order_no = ? and ordi.seller_id = ? group by 1");
        $stmt->bind_param("isis", $order_no_sub,$sellerId_sub,$order_no,$sellerId);
      }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderDetailAdmin($order_no,$sellerId,$sel_type,$ord_tm)
    {
      if ($sel_type == "SELLER") {
        $order_sel_pay = "if(prd.taxfree_yn='1',ord.order_sel_costpr,round(order_sel_costpr*1.1))";
      }else {
        $order_sel_pay = "ord.order_pay";
      }
      if (isset($ord_tm)) {
        $ordTmWhere = " and ord.ORDER_DEADLINE_TM = $ord_tm ";
      }else {
        $ordTmWhere = "";
      }
        // $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.prod_cont, prd.prod_wgt, $order_sel_pay,
        //   ord.prod_order_cnt, prd.fact_name,prd.sale_unit,ord.order_no,ord.order_item_no,ord.seller_id,sel_pcd.SELLER_PROD_CD,
        //   ord.order_deadline_tm,prd.taxfree_yn from TB_PROD prd join TB_ORDER_ITEM ord on prd.prod_cd = ord.prod_cd
        //   join TB_SELLER sel_activ on ord.seller_id = sel_activ.seller_id join TB_SELLER_PROD_CD sel_pcd
        //    on concat(ord.prod_cd,'_',ord.seller_id) =  concat(sel_pcd.prod_cd,'_',sel_pcd.seller_id) where ord.order_no = ?
        //    and sel_activ.activ_yn = 1 and ord.seller_id = ? order by 1");
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.prod_cont, prd.prod_wgt, $order_sel_pay,
          ord.prod_order_cnt, prd.fact_name,prd.sale_unit,ord.order_no,ord.order_item_no,ord.seller_id,sel_pcd.SELLER_PROD_CD,
          ord.order_deadline_tm,prd.taxfree_yn,sel_p.point_order_yn,ord.item_memo,ord.ARRIVE_DATE,cond.STN_COND_NAME from TB_PROD prd join TB_ORDER_ITEM ord on prd.prod_cd = ord.prod_cd
          join TB_SELLER sel_activ on ord.seller_id = sel_activ.seller_id left outer join TB_SELLER_PROD_CD sel_pcd
           on ord.seller_id=sel_pcd.seller_id and ord.prod_cd=sel_pcd.prod_cd
           left outer join TB_SELLER_PROD_PRICE sel_p on ord.seller_id = sel_p.seller_id and  sel_pcd.SELLER_PROD_CD = sel_p.SELLER_PROD_CD
           join TB_STN_COND cond on prd.STN_COND_CD = cond.STN_COND_CD
           where ord.order_no = ?
           and sel_activ.activ_yn = 1 and ord.seller_id = ? $ordTmWhere order by 1");
        $stmt->bind_param("is", $order_no,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectOrderDetailAdmin_detail($order_no,$sellerId)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.prod_cont, prd.prod_wgt, if(prd.taxfree_yn='1',ord.order_sel_costpr,round(ord.order_sel_costpr*1.1)), ord.prod_order_cnt, prd.fact_name,prd.sale_unit,ord.order_no,ord.order_item_no,ord.seller_id,sel_pcd.SELLER_PROD_CD,ord.ORDER_DEADLINE_TM from TB_PROD prd join TB_ORDER_ITEM ord on prd.prod_cd = ord.prod_cd join TB_SELLER sel_activ on ord.seller_id = sel_activ.seller_id join TB_SELLER_PROD_CD sel_pcd on concat(ord.prod_cd,'_',ord.seller_id) =  concat(sel_pcd.prod_cd,'_',sel_pcd.seller_id) where ord.order_no = ? and sel_activ.activ_yn = 1 and ord.seller_id = ? order by 1");
        $stmt->bind_param("is", $order_no,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function cartMSList_detail($cust_id,$cust_id2,$cust_id3,$sel_id,$tm)
    {
        $stmt = $this->conn->prepare("SELECT pt.stn_cond_cd,cart.seller_id,
       cart.prod_cd,
       pt.prod_name,
       pt.prod_cont,
       pt.prod_wgt,
       cart.prod_count,
       pt.sale_unit,
       $this->evetn_cart_qury(
       IF(Instr('$this->JinhyunPom', sel_pc.seller_id),
       IF(pt.taxfree_yn =
       0, Round(sel_pp.seller_prod_price * 1.1), sel_pp.seller_prod_price),
       ( IF(cart.prod_cd = discunt.prod_cd, IF(pt.taxfree_yn = 0
         , Round(Round(
                   Round(( sel_pp.seller_prod_price / 0.95 ) / (
                               ( 100 - selcust.margin_rate ) * 0.01 ), -1) * ( (
                   100 - discunt.discount_rate ) * 0.01 ), -1) * 1.1)
                 , Round(
                   Round(( sel_pp.seller_prod_price / 0.95 ) / (
                         ( 100 - selcust.margin_rate ) * 0.01 ), -1) * ( (
                   100 - discunt.discount_rate ) * 0.01 ), -1)),
         IF(pt.taxfree_yn = 0, Round(Round(
                                     Round(
                               ( sel_pp.seller_prod_price / 0.95 ) / (
                               ( 100 - selcust.margin_rate ) * 0.01 ), -1),
         -1) * 1.1)
, Round(
  Round(( sel_pp.seller_prod_price / 0.95 ) / (
                          ( 100 - selcust.margin_rate ) * 0.01 ), -1)))) ))) END AS
price,
pt.fact_name,
sel.seller_name,
sel.tel_no,
sel_pp.order_deadline_tm,
selcust.min_order_pr,
pt.img
,
ifnull(
IF(Instr('$this->JinhyunPom', sel_pc.seller_id), sel_pp.seller_prod_price, Round(
Round(( sel_pp.seller_prod_price / 0.95 ) / (
     ( 100 - selcust.margin_rate ) * 0.01 ), -1) * (
( 100 - discunt.discount_rate ) * 0.01 ), -1)),
Round(
Round(( sel_pp.seller_prod_price / 0.95 ) / (
     ( 100 - 0 ) * 0.01 ), -1) * (
( 100 - 0 ) * 0.01 ), -1)),
pt.taxfree_yn,
sel_pp.seller_prod_price,
sel_pp.point_order_yn,
cart.cart_memo
FROM   (SELECT *
 FROM   TB_CART
 WHERE  cust_id = ?) cart
join TB_PROD pt
  ON cart.prod_cd = pt.prod_cd
join TB_SELLER sel
  ON cart.seller_id = sel.seller_id
join TB_SELLER_PROD_CD sel_pc
  ON Concat(cart.prod_cd, '_', cart.seller_id) =
     Concat(sel_pc.prod_cd, '_', sel_pc.seller_id)
join (SELECT *
      FROM   TB_SELLER_PROD_PRICE
      WHERE  order_deadline_tm = $tm) sel_pp
  ON Concat(sel_pc.seller_prod_cd, '_', sel_pc.seller_id) =
     Concat(sel_pp.seller_prod_cd, '_', sel_pp.seller_id)
join (SELECT *
      FROM   TB_SELLER_BY_CUST
      WHERE  cust_id = ?) selcust
  ON cart.seller_id = selcust.seller_id
left join (SELECT *
           FROM   TB_PROD_DISCOUNT
           WHERE  cust_id = ?) discunt
       ON cart.seller_id = discunt.seller_id
          AND cart.prod_cd = discunt.prod_cd
WHERE  cart.seller_id = ?
AND sel_pc.seller_prod_cd = sel_pp.seller_prod_cd
AND sel_pc.seller_id = sel.seller_id ");
 //concat(cart.seller_id,'_',cart.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->bind_param("ssss", $cust_id,$cust_id2,$cust_id3,$sel_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CART_NOT_EXIST;
        }
    }

    public function selectOrderDetailMSG($order_no)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, ord.order_pay, ord.prod_order_cnt, prd.fact_name,prd.sale_unit ,sel_activ.seller_name from TB_PROD prd join TB_ORDER_ITEM ord on prd.prod_cd = ord.prod_cd join TB_SELLER sel_activ on ord.seller_id = sel_activ.seller_id where ord.order_no = ? and sel_activ.activ_yn = 1 order by 1");
        $stmt->bind_param("is", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }


    public function selectOrderTrackSeller($order_no)
    {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,ts.seller_name,his.payment_pr,ord.seller_id from TB_ORDER_ITEM ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_SELLER ts on ts.seller_id = ord.seller_id join TB_CUST_PAYMENT_HIS his on concat(his.order_no,'_',his.seller_id)=concat(ord.order_no,'_',ord.seller_id) where his.order_no = ? and (ord.order_cond_cd = '01' or ord.order_cond_cd = '02' or ord.order_cond_cd = '03') group by 4");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectOrderTrackCancelSeller($order_no)
    {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,ts.seller_name,his.payment_pr,ord.seller_id
          from TB_ORDER_ITEM ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd
          join TB_SELLER ts on ts.seller_id = ord.seller_id join (select * from TB_CUST_PAYMENT_HIS order by payment_date desc ) his
          on concat(his.order_no,'_',his.seller_id)=concat(ord.order_no,'_',ord.seller_id)
          where his.order_no = ? and (ord.order_cond_cd = '04' or ord.order_cond_cd = '05' or ord.order_cond_cd = '08') group by 4");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectOrderTrackSellerWhere($order_no,$order_cond_cd)
    {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,ts.seller_name,his.payment_pr,ord.seller_id from TB_ORDER_ITEM ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_SELLER ts on ts.seller_id = ord.seller_id join (select * from TB_CUST_PAYMENT_HIS order by payment_date desc ) his on concat(his.order_no,'_',his.seller_id)=concat(ord.order_no,'_',ord.seller_id) where his.order_no = ? and ord.order_cond_cd = ? group by 4");
        $stmt->bind_param("is", $order_no,$order_cond_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }


    public function selectCirculationProduct($seller_id, $select_type_product, $search_textfield)
    {
      if($select_type_product == '상품코드' &&  $search_textfield != null) {
          $stmt = $this->conn->prepare("SELECT prd.prod_cd,prd.prod_name,prd.prod_wgt,prd.origin_name, prd.fact_name, prd.sale_unit from TB_PROD prd left join (select prod_cd from TB_PROD_BY_SELLER where seller_id = ?) prd_sel on prd.prod_cd = prd_sel.prod_cd where prd.prod_cd like ? and prd_sel.prod_cd is null order by prd.prod_cd asc");
          $stmt->bind_param("si", $seller_id, $search_textfield);
        } else if($select_type_product == '상품명' && $search_textfield != null) {
          $stmt = $this->conn->prepare("SELECT prd.prod_cd,prd.prod_name,prd.prod_wgt,prd.origin_name, prd.fact_name, prd.sale_unit from TB_PROD prd left join (select prod_cd from TB_PROD_BY_SELLER where seller_id = ?) prd_sel on prd.prod_cd = prd_sel.prod_cd where  prd.prod_name like CONCAT('%', ?, '%') and prd_sel.prod_cd is null order by prd.prod_cd asc");
          $stmt->bind_param("ss", $seller_id, $search_textfield);
        } else {
          $stmt = $this->conn->prepare("SELECT prd.prod_cd,prd.prod_name,prd.prod_wgt,prd.origin_name, prd.fact_name, prd.sale_unit from TB_PROD prd left join (select prod_cd from TB_PROD_BY_SELLER where seller_id = ?)  prd_sel on prd.prod_cd = prd_sel.prod_cd where prd_sel.prod_cd is null order by prd.prod_cd asc");
          $stmt->bind_param("s", $seller_id);
        }
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
    }

    public function updateOrderCompleteAdmin($order_cond_cd, $order_no,$sellerId,$tmDate)
    {
        if(isset($tmDate)){
          if ($tmDate == "Cancel") {
            $arrive_date = NULL;
            $tmWhere = " ,ARRIVE_DATE = ?";
          }else {
            $tmWhere = " ,ARRIVE_DATE = '$tmDate'";
          }
          // echo "$tmWhere";
        }else {
          $tmWhere = "";
        }
        $stmt = $this->conn->prepare("UPDATE TB_ORDER_ITEM set order_cond_cd = ? $tmWhere where order_no = ? and seller_id = ?");
        if(isset($tmDate) && $tmDate == "Cancel"){
          $stmt->bind_param("ssis",$order_cond_cd,$arrive_date,$order_no,$sellerId);
        }else {
        $stmt->bind_param("sis", $order_cond_cd, $order_no,$sellerId);
        }
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }
    public function selectArriveDate($nowDate,$tm)
    {
      // $nowDate = "2020-11-23";
      // $tm = 2;
      // $nowDate = "2020-11-23";
      // $tm = 0;
        $stmt = $this->conn->prepare("SELECT subdate('$nowDate',
        interval (-(SELECT IFNULL(count(DELIV_YN),0) as 입고날짜계산일 FROM TB_CALENDAR
        WHERE CALENDAR_DATE BETWEEN '$nowDate'
        and subdate('$nowDate', interval -$tm day) and DELIV_YN = 'N')-$tm) day)
        from dual");
        // $stmt->bind_param("sis", $order_cond_cd, $order_no,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }


        public function insertManagerOrderHis($admin_id,$order_no,$seller_id,$order_cond_cd)
        {
            // echo "($admin_id,$order_no,$seller_id,$order_cond_cd)";
            $stmt = $this->conn->prepare("INSERT INTO TB_MANAGER_ORDER_HIS(ADMIN_ID,ORDER_NO,SELLER_ID,ORDER_COND_CD,REG_DATE) VALUES (?,?,?,?,now())");
            $stmt->bind_param("siss", $admin_id,$order_no,$seller_id,$order_cond_cd);
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
            } else {
                return INSERT_FAILED;
            }
        }

        public function insertMSGHis($MSG_YN,$ORDER_NO,$MSG_CD,$MSG_MEMO,$MSG_ID,$MSG_INFO,$TEL)
        {
            $stmt = $this->conn->prepare("INSERT INTO TB_MSG(MSG_YN,ORDER_NO,MSG_CD,MSG_MEMO,MSG_ID,MSG_INFO,TEL,REG_DATE)
            VALUES (?,?,?,?,?,?,?,now())");
            $stmt->bind_param("sssssss", $MSG_YN,$ORDER_NO,$MSG_CD,$MSG_MEMO,$MSG_ID,$MSG_INFO,$TEL);
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
            } else {
                return INSERT_FAILED;
            }
        }

        public function insertManagerUpdateHis($ADMIN_ID,$URL,$IP,$MEMO)
        {
            $stmt = $this->conn->prepare("INSERT INTO TB_MANAGER_UPDATE_HIS(ADMIN_ID, URL, IP, MEMO, REG_DATE) VALUES (?,?,?,?,now())");
            $stmt->bind_param("ssss", $ADMIN_ID, $URL, $IP, $MEMO);
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
            } else {
                return INSERT_FAILED;
            }
        }

        public function insertManagerSellerCdHis($ADMIN_ID,$sel_id,$prod_cd,$sel_cd,$befor_cd)
        {
          // if ($none == "NONE") {
          //   $select = "SELECT '$ADMIN_ID','$prod_cd','$sel_id','1','1',
          //   '1','1','$sel_cd',now()
          //   FROM TB_SELLER_PROD_PRICE limit 1";
          // }else {

          // }
            $stmt = $this->conn->prepare("INSERT INTO TB_MANAGER_SELLER_CD_UPDATE_HIS
            (ADMIN_ID, PROD_CD,SELLER_ID, SELLER_PROD_CD, SELLER_PROD_NAME, SELLER_PROD_PRICE, ORDER_DEADLINE_TM, UPDATE_SELLER_PROD_CD, REG_DATE)
            SELECT ?,?,?,if(count(sel_cd.SELLER_PROD_CD) > 0,sel_cd.SELLER_PROD_CD,'$befor_cd'),price.SELLER_PROD_NAME,
            price.SELLER_PROD_PRICE,price.ORDER_DEADLINE_TM,?,now()
            FROM TB_SELLER_PROD_CD sel_cd join TB_SELLER_PROD_PRICE price
            on sel_cd.SELLER_ID = price.SELLER_ID and sel_cd.SELLER_PROD_CD = price.SELLER_PROD_CD
            WHERE sel_cd.PROD_CD = ? and sel_cd.SELLER_ID = ?");

//rollback;
            $stmt->bind_param("ssssss", $ADMIN_ID,$prod_cd,$sel_id,$sel_cd, $prod_cd, $sel_id);
             $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
              return $g;
                // return INSERT_COMPLETED;
            } else {
               return $g;
                // return INSERT_FAILED;
            }
        }



    public function selectProductAdmin($seller_id, $select_type_product_1, $select_type_product_2, $search_textfield)
    {
      if($select_type_product_1 == '2' ) {
        if($select_type_product_2 == '상품코드' &&  $search_textfield != null) {
          $stmt = $this->conn->prepare(
            "SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn
            from TB_PROD prd
            join TB_PROD_BY_SELLER prd_sel
            on prd.prod_cd = prd_sel.prod_cd
            join TB_STN_COND stn
            on prd.stn_cond_cd = stn.stn_cond_cd
            where prd_sel.seller_id = ? and prd.prod_cd like ?
            order by prd.prod_cd asc");
          $stmt->bind_param("si", $seller_id, $search_textfield);
        } else if($select_type_product_2 == '상품명' &&  $search_textfield != null) {
          $stmt = $this->conn->prepare(
            "SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn
            from TB_PROD prd
            join TB_PROD_BY_SELLER prd_sel
            on prd.prod_cd = prd_sel.prod_cd
            join TB_STN_COND stn
            on prd.stn_cond_cd = stn.stn_cond_cd
            where prd_sel.seller_id = ? and prd.prod_name like CONCAT('%', ?, '%')
            order by prd.prod_cd asc ");
          $stmt->bind_param("ss", $seller_id, $search_textfield);
        } else {
            $stmt = $this->conn->prepare(
              "SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn
              from TB_PROD prd
              join TB_PROD_BY_SELLER prd_sel
              on prd.prod_cd = prd_sel.prod_cd
              join TB_STN_COND stn
              on prd.stn_cond_cd = stn.stn_cond_cd
              where prd_sel.seller_id = ?
              order by prd.prod_cd asc");
            $stmt->bind_param("s", $seller_id);
          }
      } else {
        if($select_type_product_2 == '상품코드' &&  $search_textfield != null) {
          $stmt = $this->conn->prepare(
            "SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn
            from TB_PROD prd
            join TB_PROD_BY_SELLER prd_sel
            on prd.prod_cd = prd_sel.prod_cd
            join TB_STN_COND stn
            on prd.stn_cond_cd = stn.stn_cond_cd
            where prd_sel.seller_id = ? and prd_sel.sale_yn = ? and prd.prod_cd like ?
            order by prd.prod_cd asc");
          $stmt->bind_param("sii", $seller_id, $select_type_product_1, $search_textfield);
        } else if($select_type_product_2 == '상품명' &&  $search_textfield != null) {
          $stmt = $this->conn->prepare(
            "SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn
            from TB_PROD prd
            join TB_PROD_BY_SELLER prd_sel
            on prd.prod_cd = prd_sel.prod_cd
            join TB_STN_COND stn
            on prd.stn_cond_cd = stn.stn_cond_cd
            where prd_sel.seller_id = ? and prd_sel.sale_yn = ? and prd.prod_name like CONCAT('%', ?, '%')
            order by prd.prod_cd asc ");
          $stmt->bind_param("sis", $seller_id, $select_type_product_1, $search_textfield);
        } else {
          $stmt = $this->conn->prepare(
            "SELECT prd.prod_cd, prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn
            from TB_PROD prd
            join TB_PROD_BY_SELLER prd_sel
            on prd.prod_cd = prd_sel.prod_cd
            join TB_STN_COND stn
            on prd.stn_cond_cd = stn.stn_cond_cd
            where prd_sel.seller_id = ? and prd_sel.sale_yn = ?
            order by prd.prod_cd asc ");
          $stmt->bind_param("si", $seller_id, $select_type_product_1);
        }
      }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        }else {
          if($select_type_product_1 == 1){
            return SELECT_FAILED_ing;
          }else if($select_type_product_1 == 2){
            return SELECT_FAILED_stop;
          }else {
              return SELECT_FAILED;
          }
        }
    }

    public function selectProductModifyAdmin($seller_id, $prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT(prd.prod_cd), prd.prod_name, prd.origin_name, prd.prod_wgt, prd_sel.prod_price, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd_sel.sale_yn, prd_sel.sold_yn, prd.taxfree_yn, (select cls_cd.class_name from TB_CLASS_CD cls_cd where cls_cd.class_cd = prd.first_class_cd), (select cls_cd.class_name from TB_CLASS_CD cls_cd where cls_cd.class_cd = prd.second_class_cd), (select cls_cd.class_name from TB_CLASS_CD cls_cd where cls_cd.class_cd = prd.third_class_cd) from TB_PROD prd join TB_PROD_BY_SELLER prd_sel on prd.prod_cd = prd_sel.prod_cd join TB_STN_COND stn on prd.stn_cond_cd = stn.stn_cond_cd where prd_sel.seller_id = ? and prd.prod_cd = ?");
        $stmt->bind_param("si", $seller_id, $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectProductInsertAdmin($prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT(prd.prod_cd), prd.prod_name, prd.origin_name, prd.prod_wgt, prd.fact_name, prd.sale_unit, stn.stn_cond_name, prd.taxfree_yn, (select cls_cd.class_name from TB_CLASS_CD cls_cd where cls_cd.class_cd = prd.first_class_cd), (select cls_cd.class_name from TB_CLASS_CD cls_cd where cls_cd.class_cd = prd.second_class_cd), (select cls_cd.class_name from TB_CLASS_CD cls_cd where cls_cd.class_cd = prd.third_class_cd) from TB_PROD prd join TB_STN_COND stn on prd.stn_cond_cd = stn.stn_cond_cd where prd.prod_cd = ?");
        $stmt->bind_param("i", $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateProductModifyAdmin($prod_cd, $sold_yn, $sale_yn, $prod_price, $seller_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_PROD_BY_SELLER set sold_yn = ?, sale_yn = ?, prod_price = ? where prod_cd = ? and seller_id = ?");
        $stmt->bind_param("iiiis", $sold_yn, $sale_yn, $prod_price, $prod_cd, $seller_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function insertProductModifyAdmin($prod_cd, $sold_yn, $sale_yn, $prod_price, $seller_id)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD_BY_SELLER (prod_cd, sold_yn, sale_yn, prod_price, seller_id) VALUES(?,?,?,?,?)");
        $stmt->bind_param("iiiis", $prod_cd, $sold_yn, $sale_yn, $prod_price, $seller_id);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function deleteProductAdmin($prod_cd, $seller_id)
    {
        $stmt = $this->conn->prepare("DELETE from TB_PROD_BY_SELLER where prod_cd = ? and seller_id = ?");
        $stmt->bind_param("is", $prod_cd, $seller_id);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }
    // ==나연씨 추가 DB정보==//
    // notice-mgm.php
    // notice-mgm.php
    public function selectCirculationNotice($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT noti.notice_no, sel.seller_name, noti.notice_title, noti.reg_date, group_concat(ans.notice_answer),sel.seller_id from TB_NOTICE noti
          join TB_SELLER sel on noti.seller_id = sel.seller_id left join TB_NOTICE_ANS ans on noti.NOTICE_NO = ans.notice_no where noti.cust_id = ? group by noti.notice_no order by noti.notice_no desc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // a-notice-mgm.php
    public function selectCirculationNoticeAdmin($seller_id)
    {
      if (isset($seller_id) && !empty($seller_id)) {
        $where_sel = "where noti.seller_id = '$seller_id'";
      }else {
        $where_sel = "";
      }
        $stmt = $this->conn->prepare("SELECT noti.notice_no, cst.business_name, noti.notice_title, noti.reg_date, group_concat(ans.notice_answer)
from TB_NOTICE noti
join TB_CUST cst on noti.cust_id = cst.cust_id
left join TB_NOTICE_ANS ans on noti.NOTICE_NO = ans.notice_no
$where_sel
group by noti.notice_no
order by noti.notice_no desc");
        // $stmt->bind_param("s", $seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // notice-mgm.php
    public function selectCirculationNoticeSeller()
    {
        $stmt = $this->conn->prepare("SELECT seller_name, seller_id from TB_SELLER where activ_yn = 1");
        $stmt->bind_param();
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // notice-detail.php
    public function selectNoticeDetail($notice_no)
    {
        $stmt = $this->conn->prepare("SELECT ans.notice_answer,sel.seller_id,sel.seller_name, noti_cd.notice_cond_name, noti.notice_title, noti.notice_main, noti.reg_date, noti.notice_answer, noti.notice_answer_Date,ans.reg_date
          from TB_NOTICE noti join TB_NOTICE_COND noti_cd on noti.notice_cond_cd = noti_cd.notice_cond_cd join TB_SELLER sel on noti.seller_id = sel.seller_id left join TB_NOTICE_ANS ans  on noti.NOTICE_NO = ans.notice_no where noti.notice_no = ?");
        $stmt->bind_param("i", $notice_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectuserNoticeDetail($notice_no)
    {
        $stmt = $this->conn->prepare("SELECT ans.notice_answer, cust.business_name, noti_cd.notice_cond_name, noti.notice_title, noti.notice_main, noti.reg_date, noti.notice_answer, noti.notice_answer_Date,ans.reg_date,sel.seller_id
          from TB_NOTICE noti join TB_NOTICE_COND noti_cd on noti.notice_cond_cd = noti_cd.notice_cond_cd join TB_SELLER sel on noti.seller_id = sel.seller_id left join TB_NOTICE_ANS ans  on noti.NOTICE_NO = ans.notice_no
          join TB_CUST cust on noti.cust_id = cust.cust_id where noti.notice_no = ?");
        $stmt->bind_param("i", $notice_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // a-notice-detail.php
    public function selectNoticeDetailAdmin($notice_no)
    {
        $stmt = $this->conn->prepare("SELECT ans.notice_answer, cst.business_name, noti_cd.notice_cond_name, noti.notice_title, noti.notice_main, noti.reg_date, noti.notice_answer, noti.notice_answer_Date,ans.reg_date
from TB_NOTICE noti
join TB_NOTICE_COND noti_cd on noti.notice_cond_cd = noti_cd.notice_cond_cd
left join TB_NOTICE_ANS ans  on noti.NOTICE_NO = ans.notice_no
join TB_CUST cst on noti.cust_id = cst.cust_id where noti.notice_no = ?");
        $stmt->bind_param("i", $notice_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // a-notice-answer.php
    // public function selectNoticeAnswerInsert($notice_no)
    // {
    //     $stmt = $this->conn->prepare("SELECT cst.business_name, noti_cd.notice_cond_name, noti.notice_title, noti.notice_main, noti.reg_date from TB_NOTICE noti join TB_NOTICE_COND noti_cd on noti.notice_cond_cd = noti_cd.notice_cond_cd join TB_CUST cst on noti.cust_id = cst.cust_id where noti.notice_no = ?");
    //     $stmt->bind_param("i", $notice_no);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }
    public function selectNoticeAnswerInsert($notice_no)
    {
        $stmt = $this->conn->prepare("SELECT cst.business_name, noti_cd.notice_cond_name, noti.notice_title, noti.notice_main, noti.reg_date from TB_NOTICE noti join TB_NOTICE_COND noti_cd on noti.notice_cond_cd = noti_cd.notice_cond_cd join TB_CUST cst on noti.cust_id = cst.cust_id where noti.notice_no = ?");
        $stmt->bind_param("i", $notice_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    // // a-total-mgm.php -> 업체별 기간별 쿼리-전체 cond_none
    // public function selectTotalOrderTrackAdmin($seller_id, $business_name)
    // {
    //
    //     $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //     $stmt->bind_param("ss", $seller_id, $business_name);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }
    //
    // // a-total-mgm.php -> 업체별 기간별 쿼리-전체 cond
    // public function selectTotalOrderTrackAdminSec($seller_id, $business_name, $select_type_cond1)
    // {
    //
    //     $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and ord_cd.order_cond_cd = ? group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //     $stmt->bind_param("sss", $seller_id, $business_name, $select_type_cond1);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }
    //
    // // a-total-mgm.php -> 업체별 기간별 쿼리-기간_cond_none
    // public function selectTotalOrderTrackAdminWithDays($seller_id, $business_name, $select_type_day_tab1)
    // {
    //     if($select_type_day_tab1 == '3') {
    //       $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and date(ord.order_date) = date(sysdate()) group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ss", $seller_id, $business_name);
    //     } else if ($select_type_day_tab1 == '8') {
    //       $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and date(ord.order_date) >= date(subdate(now(), interval 1 month)) group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ss", $seller_id, $business_name);
    //     } else {
    //       $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssi", $seller_id,$business_name, $select_type_day_tab1);
    //     }
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    //
    // }
    //
    // // a-total-mgm.php -> 업체별 기간별 쿼리-기간_cond
    // public function selectTotalOrderTrackAdminWithDaysSec($seller_id, $business_name, $select_type_day_tab1, $select_type_cond1)
    // {
    //
    //     if($select_type_day_tab1 == '3') {
    //       $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and date(ord.order_date) = date(sysdate()) and ord_cd.order_cond_cd = ? group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("sss", $seller_id, $business_name,$select_type_cond1);
    //     } else if ($select_type_day_tab1 == '8') {
    //       $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and date(ord.order_date) >= date(subdate(now(), interval 1 month)) and ord_cd.order_cond_cd = ?  group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("sss", $seller_id, $business_name,$select_type_cond1);
    //     } else {
    //       $stmt = $this->conn->prepare("SELECT item.prod_cd, ord_cd.order_cond_name, count(ord_cd.order_cond_cd) as count_num, prd.prod_name, prd.origin_name, prd.prod_wgt, prd.sale_unit, prd.fact_name from TB_ORDER ord join TB_ORDER_ITEM item on item.order_no= ord.order_no join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd join TB_CUST cst on ord.cust_id = cst.cust_id where item.seller_id = ? and cst.business_name= ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ord_cd.order_cond_cd = ?  group by prd.prod_cd, ord_cd.order_cond_name order by prd.prod_cd asc, find_in_set( ord_cd.order_cond_name, '취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssis", $seller_id,$business_name, $select_type_day_tab1,$select_type_cond1);
    //     }
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    //
    // }
    //
    // // a-total-mgm.php -> 물품별 기간별 쿼리-전체_cond_none
    // public function selectSecTotalOrderTrackAdmin($seller_id, $prod_name, $select_prod_cd)
    // {
    //
    //   $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //   $stmt->bind_param("ssi", $seller_id, $prod_name, $select_prod_cd);
    //   $stmt->execute();
    //   $stmt->store_result();
    //   if ($stmt->num_rows > 0) {
    //       return $stmt;
    //   } else {
    //       return SELECT_FAILED;
    //   }
    // }
    //
    // // a-total-mgm.php -> 물품별 기간별 쿼리-전체_cond
    // public function selectSecTotalOrderTrackAdminSec($seller_id, $prod_name, $select_prod_cd, $select_type_cond2)
    // {
    //
    //   $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and ord_cd.order_cond_cd = ? group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //   $stmt->bind_param("ssis", $seller_id, $prod_name, $select_prod_cd, $select_type_cond2);
    //   $stmt->execute();
    //   $stmt->store_result();
    //   if ($stmt->num_rows > 0) {
    //       return $stmt;
    //   } else {
    //       return SELECT_FAILED;
    //   }
    // }
    //
    // // a-total-mgm.php -> 물품별 기간별 쿼리-기간_cond_none
    // public function selectSecTotalOrderTrackAdminWithDays($seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2)
    // {
    //
    //     if($select_type_day_tab2 == "3") {
    //       $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and date(ord.order_date) = date(sysdate()) group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssi", $seller_id, $prod_name, $select_prod_cd);
    //     } else if($select_type_day_tab2 == "8") {
    //       $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and date(ord.order_date) >= date(subdate(now(), interval 1 month)) group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssi", $seller_id, $prod_name, $select_prod_cd);
    //     } else {
    //       $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssii", $seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2);
    //     }
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }
    //
    // // a-total-mgm.php -> 물품별 기간별 쿼리-기간_cond
    // public function selectSecTotalOrderTrackAdminWithDaysSec($seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2, $select_type_cond2)
    // {
    //
    //     if($select_type_day_tab2 == "3") {
    //       $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and date(ord.order_date) = date(sysdate()) and ord_cd.order_cond_cd = ? group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssis", $seller_id, $prod_name, $select_prod_cd, $select_type_cond2);
    //     } else if($select_type_day_tab2 == "8") {
    //       $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and date(ord.order_date) >= date(subdate(now(), interval 1 month)) and ord_cd.order_cond_cd = ? group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssis", $seller_id, $prod_name, $select_prod_cd, $select_type_cond2);
    //     } else {
    //       $stmt = $this->conn->prepare("SELECT cst.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt from  TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd join TB_PROD prd on item.prod_cd = prd.prod_cd where item.seller_id = ? and prd.prod_name = ? and item.prod_cd = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ord_cd.order_cond_cd = ? group by ord.cust_id,item.order_cond_cd order by ord.order_date desc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료') asc");
    //       $stmt->bind_param("ssiis", $seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2, $select_type_cond2);
    //     }
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }

    // a-total-mgm.php -> 업체별 기간별 쿼리-전체 cond_none
    public function selectTotalOrderTrackAdmin($seller_id,$business_name,$day,$cond_cd)
    {
      $select = "SELECT pt.prod_cd,ord_cd.order_cond_name,
sum(prod_order_cnt) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
pt.sale_unit,pt.fact_name from TB_ORDER_ITEM item
join TB_ORDER ord on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.cust_id = ?";
    $group_order = "group by pt.prod_cd,ord_cd.order_cond_name order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc";
      // echo "실행 : $seller_id,$business_name,$day,$cond_cd / ";
      //0d,-1d,7d,1m / 출고전 : 01 , 배송중 : 02 , 배송완료 : 03 ,취소접수 : 04 , 반품완료 : 05
      //당일 : 3 전일: 1 일주일 : 7 한달 : 8
      if (empty($day) && empty($cond_cd)) {
        // echo "둘다없음 / ";
                $stmt = $this->conn->prepare("$select
        $group_order");
        $stmt->bind_param("ss", $seller_id, $business_name);
      }else if (!empty($day) && !empty($cond_cd)) {
        // echo "둘다있음 / ";
        if($day == '0d') {
                  $stmt = $this->conn->prepare("$select
          and date(ord.order_date) = date(sysdate()) and ord_cd.order_cond_cd =?
          $group_order
          ");
          $stmt->bind_param("sss", $seller_id, $business_name,$cond_cd);
        } else if ($day == '1m') {
                  $stmt = $this->conn->prepare("$select
          and date(ord.order_date) >= date(subdate(now(), interval 1 month)) and ord_cd.order_cond_cd =?
          $group_order");
          $stmt->bind_param("sss", $seller_id, $business_name,$cond_cd);
        } else {
                  $stmt = $this->conn->prepare("$select
          and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ord_cd.order_cond_cd =?
          $group_order");
          $stmt->bind_param("ssis", $seller_id,$business_name,$day,$cond_cd);
        }
      }else if (empty($day) && !empty($cond_cd)) {
        // echo "코드만있음  / ";
                  $stmt = $this->conn->prepare("$select
          and ord_cd.order_cond_cd =?
          $group_order");
          $stmt->bind_param("sss", $seller_id, $business_name,$cond_cd);
      }else {
        // echo "날짜만있음 / ";
        if($day == '0d') {
                  $stmt = $this->conn->prepare("$select
          and date(ord.order_date) = date(sysdate())
          $group_order");
          $stmt->bind_param("ss", $seller_id, $business_name);
        } else if ($day == '1m') {
                  $stmt = $this->conn->prepare("$select
          and date(ord.order_date) >= date(subdate(now(), interval 1 month))
          $group_order");
          $stmt->bind_param("ss", $seller_id, $business_name);
        } else {
                  $stmt = $this->conn->prepare("$select
          and date(ord.order_date) >= date(subdate(now(), interval ? day))
          $group_order");
          $stmt->bind_param("ssi", $seller_id,$business_name,$day);
        }
      }


        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // a-total-mgm.php -> 업체별 기간별 쿼리-전체 cond
    public function selectTotalOrderTrackAdminSec($seller_id, $business_name, $select_type_cond1)
    {
        $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
pt.sale_unit,pt.fact_name from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
and ord_cd.order_cond_cd =?
group by pt.prod_cd,ord_cd.order_cond_name
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
        $stmt->bind_param("sss", $seller_id, $business_name, $select_type_cond1);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // a-total-mgm.php -> 업체별 기간별 쿼리-기간_cond_none
    public function selectTotalOrderTrackAdminWithDays($seller_id, $business_name, $select_type_day_tab1)
    {
        if($select_type_day_tab1 == '3') {//당일
          $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
  count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
  pt.sale_unit,pt.fact_name from TB_ORDER ord
  join TB_ORDER_ITEM item on item.order_no = ord.order_no
  join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
  join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
  on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
  and date(ord.order_date) = date(sysdate())
  group by pt.prod_cd,ord_cd.order_cond_name
  order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ss", $seller_id, $business_name);
        } else if ($select_type_day_tab1 == '8') {
          $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
  count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
  pt.sale_unit,pt.fact_name from TB_ORDER ord
  join TB_ORDER_ITEM item on item.order_no = ord.order_no
  join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
  join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
  on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
  and date(ord.order_date) >= date(subdate(now(), interval 1 month))
  group by pt.prod_cd,ord_cd.order_cond_name
  order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ss", $seller_id, $business_name);
        } else {
          $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
  count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
  pt.sale_unit,pt.fact_name from TB_ORDER ord
  join TB_ORDER_ITEM item on item.order_no = ord.order_no
  join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
  join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
  on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
  and date(ord.order_date) >= date(subdate(now(), interval ? day))
  group by pt.prod_cd,ord_cd.order_cond_name
  order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssi", $seller_id,$business_name, $select_type_day_tab1);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    // a-total-mgm.php -> 업체별 기간별 쿼리-기간_cond
    public function selectTotalOrderTrackAdminWithDaysSec($seller_id, $business_name, $select_type_day_tab1, $select_type_cond1)
    {

        if($select_type_day_tab1 == '3') {
          $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
  count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
  pt.sale_unit,pt.fact_name from TB_ORDER ord
  join TB_ORDER_ITEM item on item.order_no = ord.order_no
  join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
  join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
  on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
  and date(ord.order_date) = date(sysdate()) and ord_cd.order_cond_cd =?
  group by pt.prod_cd,ord_cd.order_cond_name
  order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("sss", $seller_id, $business_name,$select_type_cond1);
        } else if ($select_type_day_tab1 == '8') {
          $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
  count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
  pt.sale_unit,pt.fact_name from TB_ORDER ord
  join TB_ORDER_ITEM item on item.order_no = ord.order_no
  join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
  join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
  on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
  and date(ord.order_date) >= date(subdate(now(), interval 1 month)) and ord_cd.order_cond_cd =?
  group by pt.prod_cd,ord_cd.order_cond_name
  order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("sss", $seller_id, $business_name,$select_type_cond1);
        } else {
          $stmt = $this->conn->prepare("SELECT pt.prod_cd,ord_cd.order_cond_name,
  count(ord_cd.order_cond_cd) as count_num,pt.prod_name,pt.prod_cont,pt.prod_wgt,
  pt.sale_unit,pt.fact_name from TB_ORDER ord
  join TB_ORDER_ITEM item on item.order_no = ord.order_no
  join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
  join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
  on ord.cust_id = cust.cust_id where item.seller_id = ? and cust.business_name = ?
  and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ord_cd.order_cond_cd =?
  group by pt.prod_cd,ord_cd.order_cond_name
  order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssis", $seller_id,$business_name, $select_type_day_tab1,$select_type_cond1);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    // a-total-mgm.php -> 물품별 기간별 쿼리-전체_cond_none
    public function selectSecTotalOrderTrackAdmin($seller_id, $prod_name, $select_prod_cd)
    {

      $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
group by ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
      $stmt->bind_param("ssi", $seller_id, $prod_name, $select_prod_cd);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }
    }

    // a-total-mgm.php -> 물품별 기간별 쿼리-전체_cond
    public function selectSecTotalOrderTrackAdminSec($seller_id, $prod_name, $select_prod_cd, $select_type_cond2)
    {

      $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
and ord_cd.order_cond_cd = ?
group by ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
      $stmt->bind_param("ssis", $seller_id, $prod_name, $select_prod_cd, $select_type_cond2);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }
    }

    // a-total-mgm.php -> 물품별 기간별 쿼리-기간_cond_none
    public function selectSecTotalOrderTrackAdminWithDays($seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2)
    {
        if($select_type_day_tab2 == "3") {
          $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
and date(ord.order_date) = date(sysdate())
group by  ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssi", $seller_id, $prod_name, $select_prod_cd);
        } else if($select_type_day_tab2 == "8") {
          $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
 and date(ord.order_date) >= date(subdate(now(), interval 1 month))
group by ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssi", $seller_id, $prod_name, $select_prod_cd);
        } else {
          $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
 and date(ord.order_date) >= date(subdate(now(), interval ? day))
group by  ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssii", $seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    // a-total-mgm.php -> 물품별 기간별 쿼리-기간_cond
    public function selectSecTotalOrderTrackAdminWithDaysSec($seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2, $select_type_cond2)
    {

        if($select_type_day_tab2 == "3") {
          $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
and date(ord.order_date) = date(sysdate())
 and ord_cd.order_cond_cd = ?
group by  ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssis", $seller_id, $prod_name, $select_prod_cd, $select_type_cond2);
        } else if($select_type_day_tab2 == "8") {
          $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
 and date(ord.order_date) >= date(subdate(now(), interval 1 month))
  and ord_cd.order_cond_cd = ?
group by  ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssis", $seller_id, $prod_name, $select_prod_cd, $select_type_cond2);
        } else {
          $stmt = $this->conn->prepare("SELECT cust.business_name,ord_cd.order_cond_name,sum(item.prod_order_cnt) as sum_cnt,pt.PROD_WGT from TB_ORDER ord
join TB_ORDER_ITEM item on item.order_no = ord.order_no
join TB_ORDER_COND_CD ord_cd on item.order_cond_cd = ord_cd.order_cond_cd
join TB_PROD pt on item.prod_cd = pt.prod_cd join TB_CUST cust
on ord.cust_id = cust.cust_id where item.seller_id = ? and pt.prod_name = ? and item.prod_cd = ?
 and date(ord.order_date) >= date(subdate(now(), interval ? day))
  and ord_cd.order_cond_cd = ?
group by  ord.CUST_ID,item.order_cond_cd
order by pt.prod_cd asc, find_in_set(ord_cd.order_cond_name,'취소접수,반품완료,출고전,배송중,배송완료')asc");
          $stmt->bind_param("ssiis", $seller_id, $prod_name, $select_prod_cd, $select_type_day_tab2, $select_type_cond2);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    // purchase_mgm.phg
    public function selectPurchaseTotal($cust_id, $month)
    {

      if($month == 0){
        $stmt = $this->conn->prepare("SELECT count(item.order_no),sum(item.prod_order_cnt),sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no where ord.cust_id = ? and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') order by total_pay desc");
        $stmt->bind_param("s", $cust_id);
      } else if($month == 1){
        $stmt = $this->conn->prepare("SELECT count(item.order_no),sum(item.prod_order_cnt),sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW())) and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') order by total_pay desc");
        $stmt->bind_param("si", $cust_id, $month);
      }else {
        $stmt = $this->conn->prepare("SELECT count(item.order_no),sum(item.prod_order_cnt),sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no where ord.cust_id = ? and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month)) and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') order by total_pay desc");
        $stmt->bind_param("si", $cust_id, $month);
      }
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }

    }
   //  ajax purchaseBottom.php
    public function selectPurchaseTotalDetail($cust_id, $selectTypePurchase, $month)
    {
      if($month == 0) {//전체
        if ($selectTypePurchase == 1) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ? and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') group by 3 order by total_pay desc");
          $stmt->bind_param("s", $cust_id);
        } else if ($selectTypePurchase == 0) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ? and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') group by 3 order by count_num desc");
          $stmt->bind_param("s", $cust_id);
        }
      } else if($month == 1){//이번달
        if ($selectTypePurchase == 1) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW())) and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') group by 3 order by total_pay desc");
          $stmt->bind_param("si", $cust_id, $month);
        } else if ($selectTypePurchase == 0) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW())) and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') group by 3 order by count_num desc");
          $stmt->bind_param("si", $cust_id, $month);
        }
      }else {//저번달
        if ($selectTypePurchase == 1) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month)) and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') group by 3 order by total_pay desc");
          $stmt->bind_param("si", $cust_id, $month);
        } else if ($selectTypePurchase == 0) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month)) and (item.order_cond_cd = '03' or item.order_cond_cd = '02' or item.order_cond_cd = '01') group by 3 order by count_num desc");
          $stmt->bind_param("si", $cust_id, $month);
        }
      }
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }

    }
    public function selectAddress($cust_id)
    {
        $stmt = $this->conn->prepare("  SELECT ADDR_CONT FROM TB_CUST where cust_id = ?");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectEmail($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT email,DELIV_POSITION,REG_DATE FROM TB_CUST where cust_id = ?");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectSellerSel($seller_id)
    {
        $stmt = $this->conn->prepare("SELECT tel_no,seller_name from TB_SELLER where seller_id = ?");
        $stmt->bind_param("s",$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectProd_ER_ITEM($er_ctg,$prod_class){
      $stmt = $this ->conn->prepare("SELECT ER_PROD_NAME,PICT_FILE_NAME from TB_ER_FAVOR_PROD_CLASS where ER_CTG_CD = ? and ER_PROD_CLASS_CD = ?");
      $stmt->bind_param("ss",$er_ctg,$prod_class);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    public function selectEr_Ctg(){
      $stmt = $this ->conn->prepare("SELECT er_ctg_name,er_ctg_cd from TB_ER_CTG where use_yn = 1 order by use_yn desc,er_ctg_name desc");
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    public function selectEr_Prod_Class(){
      $stmt = $this ->conn->prepare("SELECT er_prod_class_cd,er_prod_class_name from TB_ER_PROD_CLASS order by find_in_set(er_prod_class_name, '농산,수산,축산,가공품,잡화') asc ");
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    public function selectEr_Avg(){
      $stmt = $this ->conn->prepare("SELECT ER_AVG_SALES_MIN,ER_AVG_SALES_MAX,ER_AVG_SALES_CD from TB_ER_AVG_SALES  order by ER_AVG_SALES_CD asc");
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    // public function selectEr_cust(){
    //   $stmt = $this ->conn->prepare("SELECT er_cust_tel  from TB_ER_CUST order by cust_date desc limit 1");
    //   $stmt->execute();
    //   $stmt->store_result();
    //   if($stmt->num_rows > 0){
    //     return $stmt;
    //   }else{
    //     return SELECT_FAILED;
    //   }
    // }

    public function selectEr_No($cist_tel){
      $stmt = $this ->conn->prepare("SELECT er_no from TB_ER where er_cust_tel = ? order by er_reg_date desc limit 1");
      $stmt->bind_param("s",$cist_tel);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    public function selectEr_No_1($cist_tel,$idx){
      $stmt = $this ->conn->prepare("SELECT er_no from TB_ER where er_cust_tel = ? order by er_reg_date desc limit ?,1");
      $stmt->bind_param("si",$cist_tel,$idx);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    public function selectPhonCheck($cist_tel){
      $stmt = $this ->conn->prepare("SELECT er_cust_tel from TB_ER_CUST where er_cust_tel = ?");
      $stmt->bind_param("s",$cist_tel);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }

    public function selectProdName($prod_class_cd){
      $stmt = $this ->conn->prepare("SELECT er_prod_class_name from TB_ER_PROD_CLASS where er_prod_class_cd = ?");
      $stmt->bind_param("s",$prod_class_cd);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows > 0){
        return $stmt;
      }else{
        return SELECT_FAILED;
      }
    }



    // public function insertPhon($phon)
    // {
    //     $stmt = $this->conn->prepare("INSERT into TB_ER_CUST(ER_CUST_TEL,cust_date) values(?,now())");
    //     $stmt->bind_param("s", $phon);
    //     if ($stmt->execute()) {
    //         return INSERT_COMPLETED;
    //     } else {
    //         return INSERT_FAILED;
    //     }
    // }
    public function insertER($tel_no,$er_ctg_cd,$shop_name,$prod_class_idx,$er_addr_ul,$er_addr_li,$avg_cd){
        $stmt = $this->conn->prepare("INSERT into TB_ER(ER_CUST_TEL,ER_CTG_CD,ER_SHOP_NAME,ER_PROD_CLASS_CD,ER_SHOP_ADDR,ER_SHOP_ADDR_SUB,ER_AVG_SALES_CD,ER_REG_DATE,ER_DATE) values(?,?,?,?,?,?,?,now(),now())");
        $stmt->bind_param("sssssss",$tel_no,$er_ctg_cd,$shop_name,$prod_class_idx,$er_addr_ul,$er_addr_li,$avg_cd);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function insertErProd($er_no,$er_prod_no,$er_prod_name,$er_prod_cont,$er_prod_class_cd,$yn)
    {
        $stmt = $this->conn->prepare("INSERT into TB_ER_PROD(er_no,er_prod_no,er_prod_name,er_prod_cont,er_prod_class_cd,er_prod_add_yn) values(?,?,?,?,?,?)");
        $stmt->bind_param("iisssi",$er_no,$er_prod_no,$er_prod_name,$er_prod_cont,$er_prod_class_cd,$yn);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }
    public function selectMS_class_cd()
    {
        $stmt = $this->conn->prepare("SELECT CLASS_CD,CLASS_NAME from TB_CLASS_CD where CLASS_NO = 1");
        // $stmt->bind_param("si", $cust_id, $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMS_class_cd_1($class_cd)
    {
        $stmt = $this->conn->prepare("SELECT CLASS_CD,CLASS_NAME from TB_CLASS_CD where CLASS_NO = 1  and CLASS_CD like CONCAT ('%',?,'%')");
        $stmt->bind_param("s", $class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMS_class_cd_2($class_cd)
    {
        $stmt = $this->conn->prepare("SELECT CLASS_CD,CLASS_NAME from TB_CLASS_CD where CLASS_NO = 2 and CLASS_CD like CONCAT ('%',?,'%')");
        $stmt->bind_param("s", $class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMS_limt_no($class_cd)
    {
        $stmt = $this->conn->prepare("SELECT prod_no from TB_PROD where class_cd = ?  order by  prod_no desc LIMIT 1");
        $stmt->bind_param("s", $class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function insertMSProd($prod_no,$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD(PROD_NO,CLASS_CD,CLASS_DETAIL_CD,PROD_NAME,PROD_CONT,PROD_WGT,
SALE_UNIT,FACT_NAME,TAXFREE_YN,STN_COND_CD,ORDER_DEADLINE_TM,REG_DATE)
VALUES (?,?,?,?,?,?,?,?,?,?,?,now())");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now())");
        $stmt->bind_param("isssssssssi",$prod_no,$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }
    public function insertMSProdx($prod_cd,$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$order_deadline_tm)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD(prod_cd,class_cd,class_detail_cd,prod_name,prod_cont,prod_wgt,sale_unit,fact_name,taxfree_yn,stn_cond_cd,order_deadline_tm,reg_date)
VALUES (?,?,?,?,?,?,?,?,?,?,?,now())");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now())");
        $stmt->bind_param("ssssssssisi",$prod_cd,$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$order_deadline_tm);

        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }
    public function insertMSProds($class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD(CLASS_CD,CLASS_DETAIL_CD,PROD_NAME,PROD_CONT,PROD_WGT,
SALE_UNIT,FACT_NAME,TAXFREE_YN,STN_COND_CD,ORDER_DEADLINE_TM,REG_DATE)
VALUES (?,?,?,?,?,?,?,?,?,?,now())");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now())");
        $stmt->bind_param("sssssssssi",$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
          $g = mysqli_error($this->conn);//에러메세지출력
         return "$g";
        }
    }

    public function selectMS_limt_cd($class_cd)
    {
        $stmt = $this->conn->prepare("SELECT SUBSTR(prod_cd,4) from TB_PROD where SUBSTR(prod_cd,1,3) = ?  order by  SUBSTR(prod_cd,4) desc LIMIT 1");
        $stmt->bind_param("s", $class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function insertMSProd_cd($class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd)
    {
      // echo "분류 : $class_cd
      // 분류상세 : $class_detail_cd
      // 상품명 : $prod_name
      // 상품내용 : $prod_cont
      // 상품중량 : $prod_wgt
      // 단위 : $sale_unit
      // 생산지 : $fact_name
      // 면세여부 : $taxfree_yn
      // 상태코드 : $stn_cond_cd
      // 배송시간 : $odt
      // 상품코드 : $prod_cd";
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD(CLASS_CD,CLASS_DETAIL_CD,PROD_NAME,PROD_CONT,PROD_WGT,
SALE_UNIT,FACT_NAME,TAXFREE_YN,STN_COND_CD,ORDER_DEADLINE_TM,PROD_CD,REG_DATE)
VALUES (?,?,?,?,?,?,?,?,?,?,?,now())");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
        $stmt->bind_param("sssssssssis",$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }
    public function insertMSProds_cd($class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd)
    {
      // echo "$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd";
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD(CLASS_CD,CLASS_DETAIL_CD,PROD_NAME,PROD_CONT,PROD_WGT,
SALE_UNIT,FACT_NAME,TAXFREE_YN,STN_COND_CD,ORDER_DEADLINE_TM,PROD_CD,REG_DATE)
VALUES (?,?,?,?,?,?,?,?,?,?,?,now())");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
        $stmt->bind_param("sssssssssis",$class_cd,$class_detail_cd,$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
          $g = mysqli_error($this->conn);//에러메세지출력
         return "$g";
        }
    }
    public function updateMSProd_cd($prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$class_cd,$detail_cd)
    {
      // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
       $stmt = $this->conn->prepare("UPDATE TB_PROD SET PROD_NAME = ? ,
          PROD_CONT = ? , PROD_WGT = ? , SALE_UNIT = ? ,FACT_NAME = ? ,TAXFREE_YN = ? ,
          STN_COND_CD = ? ,ORDER_DEADLINE_TM = ? ,CLASS_CD = ? ,CLASS_DETAIL_CD = ? ,UPDATE_DATE = now() where prod_cd = ?");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
          $stmt->bind_param("sssssisisss",$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$class_cd,$detail_cd,$prod_cd);

        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function updateImgMSProd_cd($imgName,$prod_cd)
    {
      // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
       $stmt = $this->conn->prepare("UPDATE TB_PROD SET img=? where prod_cd = ?");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
          $stmt->bind_param("ss",$imgName,$prod_cd);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function deleteMSProd_cd($prod_cd)
    {
      $stmt = $this->conn->prepare("DELETE from  TB_PROD where prod_cd = ?");
//VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
        $stmt->bind_param("s",$prod_cd);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }


    public function selectMS_prod_cd($class,$class_cd,$class_cd_detail,$option,$search_textfield,$s_point,$list)
    {
        // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield,$s_point,$list";
        $echo_text = explode(" ",$search_textfield);
        $echo_text_name = $echo_text[0];
        $echo_text_cont = $echo_text[1];
        $str = "SELECT PROD_CD,CLASS_CD,CLASS_DETAIL_CD,
        PROD_NAME,PROD_CONT,PROD_WGT,SALE_UNIT,FACT_NAME,TAXFREE_YN,STN_COND_CD,
        ORDER_DEADLINE_TM,ORIGIN_NAME,REG_DATE,UPDATE_DATE FROM TB_PROD";

        if ($option == "ALL") {
          $order_by = "PROD_CD";
        }else {
          $order_by = "PROD_NAME";
        }

        if (isset($s_point) && isset($list)) {
          $limit = "order by $order_by limit $s_point,$list";
        }else {
          $limit = "order by $order_by ";
        }

        // echo "$echo_text_name / $echo_text_cont";
        if ($class == "ALL"&& $search_textfield =="") {
          // echo "전체 / 키워드X";
          $stmt = $this->conn->prepare("$str $limit");
        }else if ($class != "ALL" && $search_textfield =="") {
          // echo "분류 / 키워드X";
          if ($class_cd == "ALL") {
            // echo "/ 1차 전체";
            $stmt = $this->conn->prepare("$str
             where PROD_CD like concat('%',?,'%') $limit");
            $stmt->bind_param("s",$class);
          }else {
            if ($class_cd_detail =="ALL") {
              // echo "/ 2차 전체";
              $stmt = $this->conn->prepare("$str
               where PROD_CD like concat('%',?,'%') and class_cd = ? $limit");
              $stmt->bind_param("ss",$class,$class_cd);
            }else {
              // echo "/ 2차 분류";
              $stmt = $this->conn->prepare("$str
               where PROD_CD like concat('%',?,'%') and class_cd = ? and class_detail_cd = ? $limit");
              $stmt->bind_param("sss",$class,$class_cd,$class_cd_detail);
            }
          }
        }else if ($class == "ALL" && $search_textfield !="") {
          // echo "전체 / 키워드O";
          if($echo_text_cont == ""){
            // echo "/ 1개";
            if ($option == "ALL") {
              // echo "string";
              $stmt = $this->conn->prepare("$str where
              prod_name like concat('%','$search_textfield','%')  or
              prod_cd like concat('%','$search_textfield','%')  or
              prod_cont like concat('%','$search_textfield','%')  or
              prod_wgt like concat('%','$search_textfield','%')  or
              fact_name like concat('%','$search_textfield','%')
              $limit");
            }else {
              $stmt = $this->conn->prepare("$str where $option like concat('%','$search_textfield','%') $limit");
            }
            // $stmt->bind_param("s",$search_textfield);
          }else {
            // echo "/ 2개";
            $stmt = $this->conn->prepare("$str where prod_name like concat('%',?,'%')
            and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
            or fact_name like concat('%',?,'%')) $limit");
            $stmt->bind_param("ssss",$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
          }
        }else if ($class != "ALL" && $search_textfield !="") {
          // echo "분류 / 키워드O";
          if ($class_cd == "ALL") {
            // echo "/ 1차 전체";
            if($echo_text_cont == ""){

              if ($option == "ALL") {
                // echo "string";
                $stmt = $this->conn->prepare("$str where
                PROD_CD like concat('%',?,'%') and
                (prod_name like concat('%','$search_textfield','%')  or
                prod_cd like concat('%','$search_textfield','%')  or
                prod_cont like concat('%','$search_textfield','%')  or
                prod_wgt like concat('%','$search_textfield','%')  or
                fact_name like concat('%','$search_textfield','%'))
                $limit");
              }else {
                $stmt = $this->conn->prepare("$str
                 where PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%') $limit");
              }
              // echo "/ 1개";

              $stmt->bind_param("s",$class);
            }else {
              // echo "/ 2개";
              $stmt = $this->conn->prepare("$str where  PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
              and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
              or fact_name like concat('%',?,'%')) $limit");
              $stmt->bind_param("sssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
            }
          }else {
            if ($class_cd_detail =="ALL") {
              // echo "/ 2차 전체";
              if($echo_text_cont == ""){
                // echo "1개";
                if ($option == "ALL") {
                  // echo "string";
                  $stmt = $this->conn->prepare("$str where
                  PROD_CD like concat('%',?,'%') and
                  (prod_name like concat('%','$search_textfield','%')  or
                  prod_cd like concat('%','$search_textfield','%')  or
                  prod_cont like concat('%','$search_textfield','%')  or
                  prod_wgt like concat('%','$search_textfield','%')  or
                  fact_name like concat('%','$search_textfield','%')) and class_cd = ?
                  $limit");
                }else {
                  $stmt = $this->conn->prepare("$str
                   where PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%')
                     and class_cd = ? $limit");
                }
                $stmt->bind_param("ss",$class,$class_cd);
              }else {
                // echo "/ 2개";
                $stmt = $this->conn->prepare("$str where  PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                or fact_name like concat('%',?,'%')) and class_cd = ? $limit");
                $stmt->bind_param("ssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd);
              }
            }else {
              if($echo_text_cont == ""){
                // echo "1개";
                if ($option == "ALL") {
                  // echo "string";
                  $stmt = $this->conn->prepare("$str where
                  PROD_CD like concat('%',?,'%') and
                  (prod_name like concat('%','$search_textfield','%')  or
                  prod_cd like concat('%','$search_textfield','%')  or
                  prod_cont like concat('%','$search_textfield','%')  or
                  prod_wgt like concat('%','$search_textfield','%')  or
                  fact_name like concat('%','$search_textfield','%'))
                  and class_cd = ? and class_detail_cd = ?
                  $limit");
                }else {
                  $stmt = $this->conn->prepare("$str
                   where PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%')
                     and class_cd = ? and class_detail_cd = ? $limit");
                }

                $stmt->bind_param("sss",$class,$class_cd,$class_cd_detail);
              }else {
                // echo "/ 2개";
                $stmt = $this->conn->prepare("$str where  PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                or fact_name like concat('%',?,'%')) and class_cd = ? and class_detail_cd = ? $limit");
                $stmt->bind_param("sssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail);
              }
            }
          }

        }
        // $stmt->bind_param("sssss",$class,$class_cd,$class_cd_detail,$option,$search_textfield);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        }else {
              return SELECT_FAILED;
        }
    }
    //위랑 똑같은소스 이름만다름
    public function selectMS_prod_cd_add($class,$class_cd,$class_cd_detail,$add_input_text,$search_textfield,$s_point,$list,$seller_id)
    {
        // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield";
        $echo_text = explode(" ",$search_textfield);
        $echo_text_name = $echo_text[0];
        $echo_text_cont = $echo_text[1];
        $str = "SELECT pt.PROD_CD,pt.CLASS_CD,pt.CLASS_DETAIL_CD,
        pt.PROD_NAME,pt.PROD_CONT,pt.PROD_WGT,pt.SALE_UNIT,pt.FACT_NAME,pt.TAXFREE_YN,pt.STN_COND_CD,
        pt.ORDER_DEADLINE_TM,pt.ORIGIN_NAME,pt.REG_DATE,pt.UPDATE_DATE,sel_cd.prod_cd
        FROM (SELECT * from TB_PROD where prod_cd not like '%E%') pt left join (SELECT * from TB_SELLER_PROD_CD where seller_id = '$seller_id') sel_cd
        on pt.prod_cd = sel_cd.prod_cd where sel_cd.prod_cd is null";
        if (isset($s_point) && isset($list)) {
          $limit = "limit $s_point,$list";
        }else {
          $limit = "";
        }
          $add_text="(pt.prod_cd like concat('%','$search_textfield','%') or pt.prod_name like concat('%','$search_textfield','%') or pt.prod_cont like concat('%','$search_textfield','%') or pt.fact_name like concat('%','$search_textfield','%') or pt.prod_wgt like concat('%','$search_textfield','%'))";

        // echo "$echo_text_name / $echo_text_cont";
        if ($class == "ALL"&& $search_textfield =="") {
          // echo "전체 / 키워드X";
          $stmt = $this->conn->prepare("$str $limit");
        }else if ($class != "ALL" && $search_textfield =="") {
          // echo "분류 / 키워드X";
          if ($class_cd == "ALL") {
            // echo "/ 1차 전체";
            $stmt = $this->conn->prepare("$str
             and pt.PROD_CD like concat('%',?,'%') $limit");
            $stmt->bind_param("s",$class);
          }else {
            if ($class_cd_detail =="ALL") {
              // echo "/ 2차 전체";
              $stmt = $this->conn->prepare("$str
               and pt.PROD_CD like concat('%',?,'%') and pt.class_cd = ? $limit");
              $stmt->bind_param("ss",$class,$class_cd);
            }else {
              // echo "/ 2차 분류";
              $stmt = $this->conn->prepare("$str
               and pt.PROD_CD like concat('%',?,'%') and pt.class_cd = ? and pt.class_detail_cd = ? $limit");
              $stmt->bind_param("sss",$class,$class_cd,$class_cd_detail);
            }
          }
        }else if ($class == "ALL" && $search_textfield !="") {
          // echo "전체 / 키워드O";
          if($echo_text_cont == ""){
            // echo "/ 1개";
            $stmt = $this->conn->prepare("$str and $add_text $limit");
            // $stmt->bind_param("s",$search_textfield);
          }else {
            // echo "/ 2개";
            $stmt = $this->conn->prepare("$str and pt.prod_name like concat('%',?,'%')
            and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
            or pt.fact_name like concat('%',?,'%')) $limit");
            $stmt->bind_param("ssss",$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
          }
        }else if ($class != "ALL" && $search_textfield !="") {
          // echo "분류 / 키워드O";
          if ($class_cd == "ALL") {
            // echo "/ 1차 전체";
            if($echo_text_cont == ""){
              // echo "/ 1개";
              $stmt = $this->conn->prepare("$str
               and pt.PROD_CD like concat('%',?,'%') and $add_text $limit");
              $stmt->bind_param("s",$class);
            }else {
              // echo "/ 2개";
              $stmt = $this->conn->prepare("$str and  pt.PROD_CD like concat('%',?,'%') and pt.prod_name like concat('%',?,'%')
              and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
              or pt.fact_name like concat('%',?,'%')) $limit");
              $stmt->bind_param("sssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
            }
          }else {
            if ($class_cd_detail =="ALL") {
              // echo "/ 2차 전체";
              if($echo_text_cont == ""){
                // echo "1개";
                $stmt = $this->conn->prepare("$str
                 and pt.PROD_CD like concat('%',?,'%') and $add_text
                   and class_cd = ? $limit");
                $stmt->bind_param("ss",$class,$class_cd);
              }else {
                // echo "/ 2개";
                $stmt = $this->conn->prepare("$str and  pt.PROD_CD like concat('%',?,'%') and pt.prod_name like concat('%',?,'%')
                and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
                or pt.fact_name like concat('%',?,'%')) and pt.class_cd = ? $limit");
                $stmt->bind_param("ssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd);
              }
            }else {
              if($echo_text_cont == ""){
                // echo "1개";
                $stmt = $this->conn->prepare("$str
                 and pt.PROD_CD like concat('%',?,'%') and $add_text
                   and pt.class_cd = ? and pt.class_detail_cd = ? $limit");
                $stmt->bind_param("sss",$class,$class_cd,$class_cd_detail);
              }else {
                // echo "/ 2개";
                $stmt = $this->conn->prepare("$str and  pt.PROD_CD like concat('%',?,'%') and pt.prod_name like concat('%',?,'%')
                and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
                or pt.fact_name like concat('%',?,'%')) and pt.class_cd = ? and pt.class_detail_cd = ? $limit");
                $stmt->bind_param("sssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail);
              }
            }
          }

        }
        // $stmt->bind_param("sssss",$class,$class_cd,$class_cd_detail,$option,$search_textfield);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        }else {
              return SELECT_FAILED;
        }
    }
    //================

    public function selectMS_prod_cd_img($class,$class_cd,$class_cd_detail,$option,$search_textfield,$image,$s_point,$list)
    {
        // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield";
        $echo_text = explode(" ",$search_textfield);
        $echo_text_name = $echo_text[0];
        $echo_text_cont = $echo_text[1];
        $str = "SELECT PROD_CD,CLASS_CD,CLASS_DETAIL_CD,
        PROD_NAME,PROD_CONT,PROD_WGT,SALE_UNIT,FACT_NAME,TAXFREE_YN,STN_COND_CD,
        ORDER_DEADLINE_TM,ORIGIN_NAME,REG_DATE,UPDATE_DATE FROM TB_PROD";

        if ($option == "ALL") {
          $order_by = "PROD_CD";
        }else {
          $order_by = "PROD_NAME";
        }

        if (isset($s_point) && isset($list)) {
          $limit = "order by $order_by limit $s_point,$list";
        }else {
          $limit = "order by $order_by ";
        }

        if ($image == 'N') {
          $not_image = "and img='notimg' ";
        } else {
          $not_image = "";
        }

        // echo "$echo_text_name / $echo_text_cont";
        if ($class == "ALL"&& $search_textfield =="") {
          if ($image == 'N') {
            $not_image = str_replace('and', 'where', $not_image);
          }
          // echo "전체 / 키워드X" / 이미지;
          $stmt = $this->conn->prepare("$str $not_image $limit");
        }else if ($class != "ALL" && $search_textfield =="") {
          // echo "분류 / 키워드X";
          if ($class_cd == "ALL") {
            // echo "/ 1차 전체";
            $stmt = $this->conn->prepare("$str
             where PROD_CD like concat('%',?,'%') $not_image $limit");
            $stmt->bind_param("s",$class);
          }else {
            if ($class_cd_detail =="ALL") {
              // echo "/ 2차 전체";
              $stmt = $this->conn->prepare("$str
               where PROD_CD like concat('%',?,'%') and class_cd = ? $not_image $limit");
              $stmt->bind_param("ss",$class,$class_cd);
            }else {
              // echo "/ 2차 분류";
              $stmt = $this->conn->prepare("$str
               where PROD_CD like concat('%',?,'%') and class_cd = ? and class_detail_cd = ? $not_image $limit");
              $stmt->bind_param("sss",$class,$class_cd,$class_cd_detail);
            }
          }
        }else if ($class == "ALL" && $search_textfield != "") {
          // echo "전체 / 키워드O";
          if($echo_text_cont == ""){
            if ($option == "ALL") {
              // echo "string";
              $stmt = $this->conn->prepare("$str where
              (prod_name like concat('%','$search_textfield','%')  or
              prod_cd like concat('%','$search_textfield','%')  or
              prod_cont like concat('%','$search_textfield','%')  or
              prod_wgt like concat('%','$search_textfield','%')  or
              fact_name like concat('%','$search_textfield','%'))
              $not_image $limit");
            }else {
              $stmt = $this->conn->prepare("$str where $option like concat('%','$search_textfield','%') $not_image $limit");
            }
            $stmt->bind_param("s",$search_textfield);
          }else {
            // echo "/ 2개";
            $stmt = $this->conn->prepare("$str where prod_name like concat('%',?,'%')
            and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
            or fact_name like concat('%',?,'%')) $not_image $limit");
            $stmt->bind_param("ssss",$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
          }
        }else if ($class != "ALL" && $search_textfield !="") {
          // echo "분류 / 키워드O";
          if ($class_cd == "ALL") {
            // echo "/ 1차 전체";
            if($echo_text_cont == ""){

              if ($option == "ALL") {
                // echo "string";
                $stmt = $this->conn->prepare("$str where
                PROD_CD like concat('%',?,'%') and
                (prod_name like concat('%','$search_textfield','%')  or
                prod_cd like concat('%','$search_textfield','%')  or
                prod_cont like concat('%','$search_textfield','%')  or
                prod_wgt like concat('%','$search_textfield','%')  or
                fact_name like concat('%','$search_textfield','%'))
                $not_image $limit");
              }else {
                $stmt = $this->conn->prepare("$str
                 where PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%') $not_image $limit");
              }
              // echo "/ 1개";

              $stmt->bind_param("s",$class);
            }else {
              // echo "/ 2개";
              $stmt = $this->conn->prepare("$str where  PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
              and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
              or fact_name like concat('%',?,'%')) $not_image $limit");
              $stmt->bind_param("sssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
            }
          }else {
            if ($class_cd_detail =="ALL") {
              // echo "/ 2차 전체";
              if($echo_text_cont == ""){
                // echo "1개";
                if ($option == "ALL") {
                  // echo "string";
                  $stmt = $this->conn->prepare("$str where
                  PROD_CD like concat('%',?,'%') and
                  (prod_name like concat('%','$search_textfield','%')  or
                  prod_cd like concat('%','$search_textfield','%')  or
                  prod_cont like concat('%','$search_textfield','%')  or
                  prod_wgt like concat('%','$search_textfield','%')  or
                  fact_name like concat('%','$search_textfield','%')) and class_cd = ?
                  $not_image $limit");
                }else {
                  $stmt = $this->conn->prepare("$str
                   where PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%')
                     and class_cd = ? $not_image $limit");
                }
                $stmt->bind_param("ss",$class,$class_cd);
              }else {
                // echo "/ 2개";
                $stmt = $this->conn->prepare("$str where  PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                or fact_name like concat('%',?,'%')) and class_cd = ? $not_image $limit");
                $stmt->bind_param("ssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd);
              }
            }else {
              if($echo_text_cont == ""){
                // echo "1개";
                if ($option == "ALL") {
                  // echo "string";
                  $stmt = $this->conn->prepare("$str where
                  PROD_CD like concat('%',?,'%') and
                  (prod_name like concat('%','$search_textfield','%')  or
                  prod_cd like concat('%','$search_textfield','%')  or
                  prod_cont like concat('%','$search_textfield','%')  or
                  prod_wgt like concat('%','$search_textfield','%')  or
                  fact_name like concat('%','$search_textfield','%'))
                  and class_cd = ? and class_detail_cd = ?
                  $not_image $limit");
                }else {
                  $stmt = $this->conn->prepare("$str
                   where PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%')
                     and class_cd = ? and class_detail_cd = ? $not_image $limit");
                }

                $stmt->bind_param("sss",$class,$class_cd,$class_cd_detail);
              }else {
                // echo "/ 2개";
                $stmt = $this->conn->prepare("$str where  PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                or fact_name like concat('%',?,'%')) and class_cd = ? and class_detail_cd = ? $not_image $limit");
                $stmt->bind_param("sssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail);
              }
            }
          }
        }
        // $stmt->bind_param("sssss",$class,$class_cd,$class_cd_detail,$option,$search_textfield);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        }else {
              return SELECT_FAILED;
        }
    }


    public function CategoryListMS($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT substring(prod_cd,1,1) as prod_cd_str,cd.CLASS_NAME FROM TB_PROD_DISCOUNT dc
        join TB_CLASS_CD cd on substring(dc.prod_cd,1,1) = cd.class_cd WHERE cust_id=? group by prod_cd_str
        order by find_in_set(cd.class_name,'농산물,수산물,축산물,가공품,잡화') asc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELLER_NOT_EXIST;
        }

    }

    public function secondClassSeller2MS($class_cd,$cust_id)
    {
        $stmt = $this->conn->prepare("SELECT  cls_cd.class_name from TB_PROD_DISCOUNT dc join TB_CLASS_CD cls_cd
on substring(dc.prod_cd,1,3) = cls_cd.class_cd where cls_cd.class_cd like concat('%',?,'%') and dc.cust_id = ? group by substring(dc.prod_cd,1,3)");
//substring(dc.prod_cd,1,3)
        $stmt->bind_param("ss", $class_cd,$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }

    }
    // public function secondMSClassCust($cust_id)
    // {
    //     $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.class_cd = cls_cd.class_cd join TB_FAVOR_PROD fav on prd.prod_cd = fav.prod_cd where prd.class_cd !='' and fav.cust_id = ?");
    //     $stmt->bind_param("s", $cust_id);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return CLASS_NOT_EXIST;
    //     }
    // }
    public function secondMSClassCust($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT distinct(cls_cd.class_name) from TB_PROD prd join TB_CLASS_CD cls_cd on prd.class_cd = cls_cd.class_cd join TB_FAVOR_PROD fav on prd.prod_cd = fav.prod_cd where prd.class_cd !='' and fav.cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }
    }

    public function typeMSClassCust($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT substring(cls_cd.class_cd,1,1) as str ,(select class_name from TB_CLASS_CD where class_cd = str) as name
        from TB_PROD prd join TB_CLASS_CD cls_cd on prd.class_cd = cls_cd.class_cd join TB_FAVOR_PROD fav
        on prd.prod_cd = fav.prod_cd where prd.class_cd !='' and fav.cust_id = ? group by str
        order by find_in_set(name, '농산물,수산물,축산물,가공품,잡화') asc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CLASS_NOT_EXIST;
        }
    }


    public function selectMSFavoriteList($cust_id,$cust_id1,$cust_id2,$list_count)
    {
      // echo "$list_count";
      // $list_count = 0;
      //공통 정렬 쿼리
      // $all_by = "(CASE prd.prod_wgt * 1 WHEN prd.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),prd.prod_wgt *1 asc, prd.fact_name asc";
      $all_by = "prd.prod_name asc,prd.prod_cont asc,prd.fact_name asc,prd.prod_wgt asc,price asc";
      switch ($list_count) {
      case '0': // 기본
        // $order_by_list = "prd.prod_name asc,prd_sel.seller_id asc";
        // prd.prod_wgt regexp '[0-9]' asc,
        // CASE WHEN prd.prod_wgt REGEXP '[0-9].*' THEN 1
        // ELSE 0 END
        //,prd.prod_wgt *1 asc
        // $order_by_list = "$all_by,prd.prod_name,prd.prod_cont asc,price asc";
        $order_by_list = "$all_by";
        // $order_by_list = "prd.prod_name asc,prd.fact_name asc,prd.prod_wgt";
      break;
      case '1': //상품명순
        // $order_by_list = "prd.prod_name asc,$all_by,prd.prod_cont asc,price asc";
        $order_by_list = "$all_by";
        //,prd_sel.seller_id asc
      break;
      case '2': //가격순
      // $order_by_list = "price asc,$all_by,prd.prod_cont asc";
      $order_by_list = "price asc,$all_by";
      break;
      case '3'://주문수량순(주문빈도)
      // $order_by_list = "item.cnt_sum desc,$all_by,item.cnt_prod desc,prd.prod_cont asc,price asc";
      $order_by_list = "item.cnt_sum desc,item.cnt_prod desc,$all_by";
      break;
      case '4'://부류순
        // $order_by_list = "find_in_set(substring(cls_cd.class_cd,1,1),'A,F,L,P,G') asc,$all_by,prd.prod_name asc,prd.prod_cont asc,price asc";
        $order_by_list = "find_in_set(substring(cls_cd.class_cd,1,1),'A,F,L,P,G') asc,$all_by";
      break;
      default:
      // $order_by_list = "prd.prod_name asc,$all_by,prd.prod_cont asc,price asc";
      $order_by_list = "$all_by";
          // code...
          break;
      }
//if(INSTR('$this->JinhyunPom',prd_sel.seller_id),sel_price.SELLER_PROD_PRICE,())

        $stmt = $this->conn->prepare("SELECT prd.STN_COND_CD,discunt.prod_cd, prd.prod_name,prd.prod_cont, prd.prod_wgt, prd.sale_unit,
          if(INSTR('$this->JinhyunPom',prd_sel.seller_id),if(prd.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
 if(prd.prod_cd = discunt.prod_cd
   ,if(prd.TAXFREE_YN = 0
     ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
     ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
   ,if(prd.TAXFREE_YN = 0
     ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
     ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))  as price,prd.origin_name, prd.fact_name,prd_sel.seller_id ,
concat(discunt.prod_cd,'_',prd_sel.seller_id)  prod_seller,sel_activ.seller_name,sel_price.ORDER_DEADLINE_TM,prd.img,sel_price.point_order_yn from (select * from TB_FAVOR_PROD where cust_id=?)  fav join TB_PROD prd on prd.prod_cd = fav.prod_cd
join TB_SELLER_PROD_CD prd_sel on fav.PROD_CD=prd_sel.PROD_CD and fav.SELLER_ID=prd_sel.SELLER_ID
join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id
left join TB_SELLER_PROD_PRICE sel_price on prd_sel.SELLER_ID=sel_price.SELLER_ID and prd_sel.SELLER_PROD_CD = sel_price.SELLER_PROD_CD
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel_activ.seller_id = selcust.seller_id
join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt on sel_activ.seller_id=discunt.seller_id and prd.prod_cd=discunt.prod_cd
join TB_CLASS_CD cls_cd on prd.class_cd = cls_cd.class_cd
left join (SELECT i.prod_cd,count(i.prod_cd) as cnt_prod,sum(i.PROD_ORDER_CNT) as cnt_sum from TB_ORDER_ITEM i join TB_ORDER o on i.ORDER_NO = o.ORDER_NO
where o.CUST_ID = '$cust_id' and (i.order_cond_cd = '03')group by i.prod_cd) item  on fav.prod_cd = item.prod_cd
where sel_activ.activ_yn = 1 and fav.prod_cd = discunt.prod_cd and prd_sel.SELLER_PROD_CD = sel_price.SELLER_PROD_CD order by $order_by_list LIMIT 0,30");
        $stmt->bind_param("sss", $cust_id,$cust_id1,$cust_id2);
        //concat(sel_activ.seller_id,'_',prd.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectMSFavoriteListWithWhere($cust_id,$cust_id1,$cust_id2,$secondclass,$sel,$start,$list,$list_count)
    {
      //역정렬
      //SELECT * FROM orderex ORDER BY length(varcharId) desc,varcharId desc
      // echo "$list_count / ~";
      // echo "$cust_id,$cust_id1,$cust_id2,$secondclass,$start,$list,$list_count";
      // $list_count = 0;
      //공통 정렬 쿼리
      // $all_by = "(CASE prd.prod_wgt * 1 WHEN prd.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),prd.prod_wgt *1 asc, prd.fact_name asc";
      $all_by = "prd.prod_name asc,prd.prod_cont asc,prd.fact_name asc,prd.prod_wgt asc,price asc";
      switch ($list_count) {
      case '0': // 기본
        // $order_by_list = "prd.prod_name asc,prd_sel.seller_id asc";
        // prd.prod_wgt regexp '[0-9]' asc,
        // CASE WHEN prd.prod_wgt REGEXP '[0-9].*' THEN 1
        // ELSE 0 END
        //,prd.prod_wgt *1 asc
        // $order_by_list = "$all_by,prd.prod_name,prd.prod_cont asc,price asc";
        $order_by_list = "$all_by";
        // $order_by_list = "prd.prod_name asc,prd.fact_name asc,prd.prod_wgt";
      break;
      case '1': //상품명순
        // $order_by_list = "prd.prod_name asc,$all_by,prd.prod_cont asc,price asc";
        $order_by_list = "$all_by";

        //,prd_sel.seller_id asc
      break;
      case '2': //가격순
      // $order_by_list = "price asc,$all_by,prd.prod_cont asc";
      $order_by_list = "price asc,$all_by";

      break;
      case '3'://주문수량순(주문빈도)
      // $order_by_list = "item.cnt_sum desc,$all_by,item.cnt_prod desc,prd.prod_cont asc,price asc";
      $order_by_list = "item.cnt_sum desc,item.cnt_prod desc,$all_by";
      break;
      case '4'://부류순
        // $order_by_list = "find_in_set(substring(cls_cd.class_cd,1,1),'A,F,L,P,G') asc,$all_by,prd.prod_name asc,prd.prod_cont asc,price asc";
        $order_by_list = "find_in_set(substring(cls_cd.class_cd,1,1),'A,F,L,P,G') asc,$all_by";
      break;
      default:
      // $order_by_list = "prd.prod_name asc,$all_by,prd.prod_cont asc,price asc";
      $order_by_list = "$all_by";
          // code...
          break;
      }
      // echo "$cust_id,$cust_id1,$cust_id2,$secondclass,$start,$list";
      if ($secondclass == "ALL") {
        $class_cd_where = "";
      }else {
        // if (strlen($secondclass) > 3) {
        //   $class_cd_where = "and sel_activ.seller_id like concat('%','$secondclass','%')";
        // }else {
          $class_cd_where = "and cls_cd.class_cd like concat('%','$secondclass','%')";
        // }
      }
      //셀러정보가있을때
      if (isset($sel) && $sel !== "") {
        $class_cd_where .= " and sel_activ.seller_id like concat('%','$sel','%')";
      }
      $stmt = $this->conn->prepare("SELECT prd.STN_COND_CD,discunt.prod_cd, prd.prod_name,prd.prod_cont, prd.prod_wgt, prd.sale_unit,
if(INSTR('$this->JinhyunPom',prd_sel.seller_id),if(prd.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
 if(prd.prod_cd = discunt.prod_cd
   ,if(prd.TAXFREE_YN = 0
     ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
     ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
   ,if(prd.TAXFREE_YN = 0
     ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
     ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))  as price,prd.origin_name, prd.fact_name,prd_sel.seller_id ,
concat(discunt.prod_cd,'_',prd_sel.seller_id)  prod_seller,sel_activ.seller_name,sel_price.ORDER_DEADLINE_TM,prd.img,sel_price.point_order_yn from (select * from TB_FAVOR_PROD where cust_id=?)  fav join TB_PROD prd on prd.prod_cd = fav.prod_cd
join TB_SELLER_PROD_CD prd_sel on fav.PROD_CD=prd_sel.PROD_CD and fav.SELLER_ID=prd_sel.SELLER_ID
join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id
left join TB_SELLER_PROD_PRICE sel_price on prd_sel.SELLER_ID=sel_price.SELLER_ID and prd_sel.SELLER_PROD_CD = sel_price.SELLER_PROD_CD
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel_activ.seller_id = selcust.seller_id
join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt on sel_activ.seller_id=discunt.seller_id and prd.prod_cd=discunt.prod_cd
join TB_CLASS_CD cls_cd on prd.class_cd = cls_cd.class_cd
left join (SELECT i.prod_cd,count(i.prod_cd) as cnt_prod,sum(i.PROD_ORDER_CNT) as cnt_sum from TB_ORDER_ITEM i join TB_ORDER o on i.ORDER_NO = o.ORDER_NO
where o.CUST_ID = '$cust_id' and (i.order_cond_cd = '03')group by i.prod_cd) item  on fav.prod_cd = item.prod_cd
where sel_activ.activ_yn = 1 $class_cd_where and fav.prod_cd = discunt.prod_cd and prd_sel.SELLER_PROD_CD = sel_price.SELLER_PROD_CD order by $order_by_list LIMIT $start,$list");
//concat(sel_activ.seller_id,'_',prd.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
      $stmt->bind_param("sss",$cust_id,$cust_id1,$cust_id2);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }
  }
    public function productListFavcd2MS($cust_id,$cust_id2,$cust_id3,$class_cd)
    {
      // $all_by = "(CASE pt.prod_wgt * 1 WHEN pt.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),pt.prod_wgt *1 asc,pt.prod_name,pt.prod_cont";
      // $all_by = "(CASE pt.prod_wgt * 1 WHEN pt.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),pt.prod_wgt *1 asc, pt.fact_name asc,pt.prod_name,pt.prod_cont,price asc";
      $all_by = "pt.prod_name asc,pt.prod_cont asc,pt.fact_name asc,pt.prod_wgt asc,price asc";
      // round(sel_price.SELLER_PROD_PRICE*(1-(selcust.MARGIN_RATE/100))) 할인율.
    //round(sel_price.SELLER_PROD_PRICE+((sel_price.SELLER_PROD_PRICE/100)*selcust.MARGIN_RATE),-1) +퍼센트
    // if ($cust_id == "1234567890") {
    //   return PRODUCT_NOT_EXIST;
    // }
        $stmt = $this->conn->prepare("SELECT pt.STN_COND_CD,discunt.prod_cd,fav.prod_cd,pt.prod_name,
pt.prod_cont,pt.prod_wgt,pt.sale_unit,
if(INSTR('$this->JinhyunPom',sel_cd.seller_id),if(pt.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
if(pt.prod_cd = discunt.prod_cd
  ,if(pt.TAXFREE_YN = 0
    ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
    ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
  ,if(pt.TAXFREE_YN = 0
    ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
    ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))  as price,
pt.fact_name,pt.fact_name,concat(sel_cd.prod_cd,'_',sel_cd.seller_id),
concat(sel_cd.prod_cd,'_',sel_cd.seller_id),sel.seller_name,sel_cd.seller_id,sel_price.ORDER_DEADLINE_TM,sel_price.point_order_yn
from TB_PROD pt join TB_SELLER_PROD_CD sel_cd on pt.prod_cd = sel_cd.prod_cd
left join TB_SELLER_PROD_PRICE sel_price on
concat(sel_cd.seller_id,'_',sel_cd.seller_prod_cd) = concat(sel_price.seller_id,'_',sel_price.seller_prod_cd)
join TB_SELLER sel on sel_cd.seller_id = sel.seller_id
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel.seller_id = selcust.seller_id
join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt on sel.seller_id=discunt.seller_id and pt.prod_cd=discunt.prod_cd
left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav on concat(sel_cd.prod_cd,'_',sel_cd.seller_id) = concat(fav.prod_cd,'_',fav.seller_id)
where pt.CLASS_CD like concat('%',?,'%') and sel_cd.SELLER_PROD_CD = sel_price.SELLER_PROD_CD order by $all_by LIMIT 0,30");
//sel_cd.prod_cd asc,sel_cd.seller_id asc
//concat(sel.seller_id,'_',pt.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->bind_param("ssss",$cust_id,$cust_id2,$cust_id3,$class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }
    public function selectProductListWithWhereFavcd2MS($cust_id,$cust_id2,$cust_id3,$secondclass,$class_cd,$sel,$start,$list)
    {

      // $all_by = "(CASE pt.prod_wgt * 1 WHEN pt.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),pt.prod_wgt *1 asc,pt.prod_name,pt.prod_cont";
      // $all_by = "(CASE pt.prod_wgt * 1 WHEN pt.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),pt.prod_wgt *1 asc, pt.fact_name asc,pt.prod_name,pt.prod_cont,price asc";
      $all_by = "pt.prod_name asc,pt.prod_cont asc,pt.fact_name asc,pt.prod_wgt asc,price asc";
      // echo "$cust_id,$cust_id2,$cust_id3,$secondclass,$class_cd,$start,$list";
      // echo "$secondclass";
      if ($secondclass == "ALL") {
        $class_cd_where = "";
      }else {
        // if (strlen($secondclass) == 10) {
        //   $class_cd_where = "discunt.seller_id = '$secondclass' and ";
        // }else {
          $class_cd_where = "cdt.class_name = '$secondclass' and ";
        // }
      }
      //셀러정보가있을때
      if (isset($sel) && $sel !== "") {
        $class_cd_where .= " discunt.seller_id like concat('%','$sel','%') and ";
      }
      // if ($cust_id == "1234567890") {
      //   return PRODUCT_NOT_EXIST;
      // }
        $stmt = $this->conn->prepare("SELECT pt.STN_COND_CD,discunt.prod_cd,fav.prod_cd,pt.prod_name,
pt.prod_cont,pt.prod_wgt,pt.sale_unit,
if(INSTR('$this->JinhyunPom',sel_cd.seller_id),if(pt.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
if(pt.prod_cd = discunt.prod_cd
  ,if(pt.TAXFREE_YN = 0
    ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
    ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
  ,if(pt.TAXFREE_YN = 0
    ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
    ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))  as price,
pt.fact_name,pt.fact_name,concat(sel_cd.prod_cd,'_',sel_cd.seller_id),
concat(sel_cd.prod_cd,'_',sel_cd.seller_id),sel.seller_name,sel_cd.seller_id,sel_price.ORDER_DEADLINE_TM,pt.img,sel_price.point_order_yn
from TB_PROD pt join TB_SELLER_PROD_CD sel_cd on pt.prod_cd = sel_cd.prod_cd
left join TB_SELLER_PROD_PRICE sel_price on
concat(sel_cd.seller_id,'_',sel_cd.seller_prod_cd) = concat(sel_price.seller_id,'_',sel_price.seller_prod_cd)
join TB_SELLER sel on sel_cd.seller_id = sel.seller_id  join TB_CLASS_CD cdt
on pt.class_cd=cdt.class_cd join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel.seller_id = selcust.seller_id
join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt on sel.seller_id=discunt.seller_id and pt.prod_cd=discunt.prod_cd
 left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav on sel_cd.prod_cd=fav.prod_cd and sel_cd.seller_id=fav.seller_id
where $class_cd_where pt.CLASS_CD like concat('%',?,'%') and sel_cd.SELLER_PROD_CD = sel_price.SELLER_PROD_CD order by $all_by LIMIT $start,$list");
//concat(sel.seller_id,'_',pt.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->bind_param("ssss",$cust_id,$cust_id2,$cust_id3,$class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }
    public function selectMS_prod_cd_detail($prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT PROD_CD,CLASS_CD,CLASS_DETAIL_CD,
          PROD_NAME,PROD_CONT,PROD_WGT,
          SALE_UNIT,FACT_NAME,TAXFREE_YN,
          STN_COND_CD,ORDER_DEADLINE_TM,ORIGIN_NAME,
          REG_DATE,UPDATE_DATE FROM TB_PROD WHERE prod_cd = ?");
        $stmt->bind_param("s", $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }
    public function selectMS_prod_cd_detail_Active($prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT PROD_CD,CLASS_CD,CLASS_DETAIL_CD,
          PROD_NAME,PROD_CONT,PROD_WGT,
          SALE_UNIT,FACT_NAME,TAXFREE_YN,
          STN_COND_CD,ORDER_DEADLINE_TM,ORIGIN_NAME,
          REG_DATE,UPDATE_DATE,PROD_ACTIV_YN FROM TB_PROD WHERE prod_cd = ?");
        $stmt->bind_param("s", $prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function selectMSClassName($cd)
    {
        $stmt = $this->conn->prepare("SELECT CLASS_NAME FROM TB_CLASS_CD WHERE CLASS_CD = ?");
        $stmt->bind_param("s", $cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function selectMSinsertSelCd($prod_cd,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT PROD_CD,SELLER_ID,SELLER_PROD_CD FROM TB_SELLER_PROD_CD WHERE prod_cd = ? and seller_id = ?");
        $stmt->bind_param("ss", $prod_cd,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateMSinsertSelCd($sel_cd,$prod_cd,$seller_id)
    {
      //echo "$sel_cd,$prod_cd,$seller_id";
        $stmt = $this->conn->prepare("UPDATE TB_SELLER_PROD_CD set SELLER_PROD_CD = ? where prod_cd = ? and seller_id = ?");
        $stmt->bind_param("sss",$sel_cd,$prod_cd,$seller_id);
        if ($stmt->execute()) {
              return UPDATE_COMPLETED;
        } else {
              return UPDATE_FAILED;
        }
    }

    public function deleteMSSelCd($prod_cd,$seller_id)
    {
      //echo "$sel_cd,$prod_cd,$seller_id";
        $stmt = $this->conn->prepare("DELETE from TB_SELLER_PROD_CD where prod_cd = ? and seller_id = ?");
        $stmt->bind_param("ss",$prod_cd,$seller_id);
        if ($stmt->execute()) {
              return DELETE_COMPLETED;
        } else {
              return DELETE_FAILED;
        }
    }

        public function deleteMSinsertSelCd($sel_cd,$prod_cd,$seller_id)
        {
          //echo "$sel_cd,$prod_cd,$seller_id";
            $stmt = $this->conn->prepare("DELETE from TB_SELLER_PROD_CD where SELLER_PROD_CD = ? and  prod_cd = ? and seller_id = ?");
            $stmt->bind_param("sss",$sel_cd,$prod_cd,$seller_id);
            if ($stmt->execute()) {
              $stmt2 = $this->conn->prepare("DELETE from TB_SELLER_PROD_PRICE where SELLER_PROD_CD = ? and seller_id = ?");
              $stmt2->bind_param("ss",$sel_cd,$seller_id);
              if ($stmt2->execute()) {
                return DELETE_COMPLETED;
              }else {
                return DELETE_FAILED;
              }
            } else {
                  return DELETE_FAILED;
            }
        }
    public function insertMSsel_cd($prod_cd,$seller_id,$seller_prod_cd)
    {
      $stmt = $this->conn->prepare("INSERT INTO TB_SELLER_PROD_CD(PROD_CD,SELLER_ID,SELLER_PROD_CD) VALUES (?,?,?)");
      $stmt->bind_param("sss",$prod_cd,$seller_id,$seller_prod_cd);
      if ($stmt->execute()) {
            return INSERT_COMPLETED;
      } else {
            return INSERT_FAILED;
      }
    }
    public function selectcust($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT tel_no FROM TB_CUST WHERE cust_id=?");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function updatecust($cust_id,$pass)
    {
      $password = md5($pass);
      $stmt = $this->conn->prepare("UPDATE TB_CUST set password = ? where cust_id = ?");
      $stmt->bind_param("ss",$password,$cust_id);
      if ($stmt->execute()) {
            return UPDATE_COMPLETED;
      } else {
            return UPDATE_FAILED;
      }
    }

    public function insertMSCart($cust_id, $prod_cd, $prod_count,$seller_id,$cart_memo)
    { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
        $stmt = $this->conn->prepare("INSERT into TB_CART (cust_id, prod_cd, prod_count,seller_id,cart_memo) values(?, ?, ?, ?,?)");
        $stmt->bind_param("ssdss", $cust_id, $prod_cd, $prod_count,$seller_id,$cart_memo);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }

    }

    public function insertIgnoreMSCart($cust_id, $prod_cd, $prod_count,$seller_id,$cart_memo)
    { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
        $stmt = $this->conn->prepare("INSERT IGNORE into TB_CART (cust_id, prod_cd, prod_count,seller_id,cart_memo) values(?, ?, ?, ?,?)");
        $stmt->bind_param("ssdss", $cust_id, $prod_cd, $prod_count,$seller_id,$cart_memo);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }

    }

    public function selectMSCart($cust_id,$prod_cd,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT prod_count from TB_CART where cust_id = ? and prod_cd = ? and seller_id = ?");
        $stmt->bind_param("sss",$cust_id, $prod_cd,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectAllMSCart($cust_id,$seller_id,$prod_cd)
    {
        $stmt = $this->conn->prepare("SELECT cust_id,prod_cd,prod_count,seller_id from tb_cart where cust_id = ? and seller_id = ? and prod_cd = ?");
        $stmt->bind_param("sss",$cust_id,$seller_id,$prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCancelOrderItem($order_no,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT order_item_no,prod_cd,prod_order_cnt,item_memo FROM TB_ORDER_ITEM WHERE order_no = ? and seller_id = ?");
        $stmt->bind_param("is",$order_no,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateMSCart($cust_id, $prod_cd, $prod_count,$seller_id)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CART set prod_count = ? where cust_id = ? and prod_cd = ? and seller_id = ?");
        $stmt->bind_param("dsss",$prod_count, $cust_id, $prod_cd,$seller_id);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }

    }

    public function cartMSList($cust_id,$cust_id2,$cust_id3,$sel_id)
    {
        $stmt = $this->conn->prepare("SELECT
cart.seller_id,cart.prod_cd,pt.prod_name,pt.PROD_CONT,
pt.prod_wgt,cart.prod_count,pt.sale_unit,
if(cart.prod_cd = discunt.prod_cd,if(pt.TAXFREE_YN = 0,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01)*1.1),round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
			   ,if(pt.TAXFREE_YN = 0,round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*1.1),round(round((sel_pp.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))  as price,pt.fact_name,sel.seller_name,sel.tel_no
from  (SELECT * from TB_CART where cust_id = ?) cart join TB_PROD pt
on cart.prod_cd = pt.prod_cd join TB_SELLER sel on
cart.seller_id = sel.seller_id join TB_SELLER_PROD_CD sel_pc
on concat(cart.prod_cd,'_',cart.seller_id) = concat(sel_pc.prod_cd,'_',sel_pc.seller_id)
 join TB_SELLER_PROD_PRICE sel_pp
on concat(sel_pc.seller_prod_cd,'_',sel_pc.seller_id) = concat(sel_pp.seller_prod_cd,'_',sel_pp.seller_id)
join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?)
selcust on cart.seller_id = selcust.seller_id
left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?)
 discunt on cart.seller_id,=discunt.seller_id and cart.prod_cd=discunt.prod_cd
 where cart.seller_id = ?");
 //concat(cart.seller_id,'_',cart.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->bind_param("ssss", $cust_id,$cust_id2,$cust_id3,$sel_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return CART_NOT_EXIST;
        }
    }

    public function deleteErrorOrder($order_no)
    {
      if(isset($order_no)){
        $stmt = $this->conn->prepare("DELETE from TB_ORDER where order_no = ?");
        $stmt->bind_param("i", $order_no);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
      }else {
        return DELETE_FAILED;
      }
    }
    public function deleteMSCart($cust_id)
    {
        $stmt = $this->conn->prepare("DELETE from TB_CART where cust_id = ?");
        $stmt->bind_param("s", $cust_id);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function deleteMSCartWhere($cust_id,$prod_cd,$sellerId)
    {
        if ($prod_cd == "SELLER") {
          $where = "and seller_id =?";
          $stmt = $this->conn->prepare("DELETE from TB_CART where cust_id = ? $where");
          $stmt->bind_param("ss", $cust_id,$sellerId);
        }else {
          $where = "and prod_cd = ? and seller_id =?";
          $stmt = $this->conn->prepare("DELETE from TB_CART where cust_id = ? $where");
          $stmt->bind_param("sss", $cust_id,$prod_cd,$sellerId);
        }
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function insertMSOrderItem($order_no,$sellerId,$prod_cd, $prod_order_cnt,$order_pay,$order_deadline_tm,$prod_price_select_orignal,$seller_price_orignal,$item_memo)
    {//유통업체거래내역
        $stmt = $this->conn->prepare("INSERT INTO TB_ORDER_ITEM (order_no, seller_id, prod_cd, prod_order_cnt,order_pay,order_deadline_tm,order_costpr,order_sel_costpr,item_memo,SELLER_PROD_CD)
        SELECT '$order_no','$sellerId', '$prod_cd', '$prod_order_cnt','$order_pay','$order_deadline_tm','$prod_price_select_orignal','$seller_price_orignal','$item_memo',if(count(SELLER_PROD_CD)>0,SELLER_PROD_CD,null) from TB_SELLER_PROD_CD where seller_id='$sellerId' and prod_cd ='$prod_cd'");
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }
    // public function insertMSOrderItem($order_no,$sellerId,$prod_cd, $prod_order_cnt,$order_pay,$order_deadline_tm,$prod_price_select_orignal,$seller_price_orignal,$item_memo)
    // {//유통업체거래내역
    //     $stmt = $this->conn->prepare("INSERT INTO TB_ORDER_ITEM (order_no, seller_id, prod_cd, prod_order_cnt,order_pay,order_deadline_tm,order_costpr,order_sel_costpr,item_memo) values (?, ?, ?, ?,?,?,?,?,?)");
    //     $stmt->bind_param("issdiiiis",$order_no,$sellerId, $prod_cd, $prod_order_cnt,$order_pay,$order_deadline_tm,$prod_price_select_orignal,$seller_price_orignal,$item_memo);
    //     if ($stmt->execute()) {
    //         return INSERT_COMPLETED;
    //     } else {
    //         return INSERT_FAILED;
    //     }
    // }

    public function selectMSOrderSellerPay($cust_id,$order_no)
    {
        $stmt = $this->conn->prepare("SELECT item.seller_id,sum(item.order_pay * item.prod_order_cnt) as payment_pr
          ,sel.DELIV_PAY,bys.MIN_ORDER_PR
          from TB_ORDER_ITEM item
          join TB_SELLER sel on item.SELLER_ID = sel.SELLER_ID
          join (SELECT *  FROM TB_ORDER where cust_id = '$cust_id') ord on item.ORDER_NO = ord.ORDER_NO
          join (SELECT *  FROM TB_SELLER_BY_CUST where cust_id = '$cust_id') bys
          on ord.CUST_ID = bys.CUST_ID and item.SELLER_ID = bys.SELLER_ID
          where item.order_no = ?
          group by 1");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrack($cust_id)
    {//pay.payment_sum
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, sum(round(item.order_pay*item.PROD_ORDER_CNT)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'CP' or payment_his_cd = 'SI' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03') group by 1 order by ord.order_date desc");
        $stmt->bind_param("s", $cust_id);
         $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectMSOrderTrackSeller($order_no,$vw,$vbnk)
    {
      if ($vw == "VW") {
        if ($vbnk == "vbnk") {
          $vbnkSelect = ",(SELECT (sum(order_pay*PROD_ORDER_CNT)-(SELECT IFNULL(sum( COUPON_DISCOUNT_PRICE),0) FROM TB_COUPON_HIS WHERE
(order_no REGEXP(SELECT REPLACE(Group_concat(order_no), ',','|') AS ORDER_NO FROM TB_ORDER
  WHERE wtid = (SELECT NULLIF(wtid,'')  FROM TB_ORDER WHERE ORDER_NO = '$order_no'))))) as sumpay FROM TB_ORDER_ITEM WHERE
          (order_no REGEXP(SELECT REPLACE(Group_concat(order_no), ',','|') AS ORDER_NO
          FROM TB_ORDER WHERE wtid = (SELECT NULLIF(wtid,'')  FROM TB_ORDER WHERE ORDER_NO = '$order_no'))))";
        }else {
          $vbnkSelect = "";
        }
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,sel.seller_name,sum(item.prod_order_cnt * item.order_pay),item.seller_id
        ,ord.wtid,bc.bn_name$vbnkSelect from TB_ORDER_ITEM item
        join TB_ORDER_COND_CD ord_cd
        on item.order_cond_cd = ord_cd.order_cond_cd
        join TB_SELLER sel on item.seller_id = sel.seller_id
        left join TB_ORDER ord on item.order_no = ord.order_no
        left join TB_BN_CD bc on SUBSTRING(ord.wtid, -2) = bc.bn_cd
        where item.order_no = ?
        and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03')
        group by item.seller_id");
      }else {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,sel.seller_name,sum(item.prod_order_cnt * item.order_pay),item.seller_id
        from TB_ORDER_ITEM item
        join TB_ORDER_COND_CD ord_cd
        on item.order_cond_cd = ord_cd.order_cond_cd
        join TB_SELLER sel on item.seller_id = sel.seller_id
        where order_no = ?
        and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03')
        group by item.seller_id");
      }

        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectorder_nototal($order_no,$sel)
    {
        $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt * item.order_pay)
from TB_ORDER_ITEM item
where order_no = ? and seller_id = ?
and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03' or item.order_cond_cd = '04')");
        $stmt->bind_param("is", $order_no,$sel);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectWtid_nototal($wtid)
    {
        $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt * item.order_pay)
from TB_ORDER_ITEM item
left join TB_ORDER ord
on item.ORDER_NO = ord.ORDER_NO
where ord.wtid like '%$wtid%'
and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or
  item.order_cond_cd = '02' or item.order_cond_cd = '03' or item.order_cond_cd = '04')");
        // $stmt->bind_param("s", $wtid);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackSellerWhere($order_no,$order_cond_cd,$vw)
    {
      if ($vw == "VW") {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,sel.seller_name,sum(item.prod_order_cnt * item.order_pay),item.seller_id
        ,ord.wtid,bc.bn_name from TB_ORDER_ITEM item
        join TB_ORDER_COND_CD ord_cd
        on item.order_cond_cd = ord_cd.order_cond_cd
        join TB_SELLER sel on item.seller_id = sel.seller_id
        left join TB_ORDER ord on item.order_no = ord.order_no
        left join TB_BN_CD bc on SUBSTRING(ord.wtid, -2) = bc.bn_cd
        where item.order_no = ?
        and item.order_cond_cd = ?
        group by item.seller_id");
      }else {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,sel.seller_name,sum(item.prod_order_cnt * item.order_pay),item.seller_id
        from TB_ORDER_ITEM item
        join TB_ORDER_COND_CD ord_cd
        on item.order_cond_cd = ord_cd.order_cond_cd
        join TB_SELLER sel on item.seller_id = sel.seller_id
        where order_no = ?
        and item.order_cond_cd = ?
        group by item.seller_id");
      }
        $stmt->bind_param("is", $order_no,$order_cond_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    // public function selectMSOrderTrackSeller($order_no)
    // {
    //     $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,ts.seller_name,his.payment_pr,ord.seller_id from TB_ORDER_ITEM ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_SELLER ts on ts.seller_id = ord.seller_id join TB_CUST_PAYMENT_HIS his on concat(his.order_no,'_',his.seller_id)=concat(ord.order_no,'_',ord.seller_id) where his.order_no = ? and (ord.order_cond_cd = '01' or ord.order_cond_cd = '02' or ord.order_cond_cd = '03') group by 4");
    //     $stmt->bind_param("i", $order_no);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }
    // public function selectMSOrderTrackSellerWhere($order_no,$order_cond_cd)
    // {
    //     $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,ts.seller_name,his.payment_pr,ord.seller_id from TB_ORDER_ITEM ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_SELLER ts on ts.seller_id = ord.seller_id join (select * from TB_CUST_PAYMENT_HIS order by payment_date desc ) his on concat(his.order_no,'_',his.seller_id)=concat(ord.order_no,'_',ord.seller_id) where his.order_no = ? and ord.order_cond_cd = ? group by 4");
    //     $stmt->bind_param("is", $order_no,$order_cond_cd);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     if ($stmt->num_rows > 0) {
    //         return $stmt;
    //     } else {
    //         return SELECT_FAILED;
    //     }
    // }

    public function selectMSOrderTrackWithDays($cust_id, $days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, sum(round(item.order_pay*item.PROD_ORDER_CNT)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'SI' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03') and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackWithMonth($cust_id, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, sum(round(item.order_pay*item.PROD_ORDER_CNT)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'SI' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03') and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackWithWhere($cust_id, $where)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, sum(round(item.order_pay*item.PROD_ORDER_CNT)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'SI' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ? group by 1 order by 1 desc");
        $stmt->bind_param("ss", $cust_id, $where);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackWithWhereAndDays($cust_id,$where,$days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, sum(round(item.order_pay*item.PROD_ORDER_CNT)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'SI' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("ssi", $cust_id, $where, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackWithWhereAndMonth($cust_id,$where, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, sum(round(item.order_pay*item.PROD_ORDER_CNT)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where (payment_his_cd = 'BW' or payment_his_cd = 'SC' or payment_his_cd = 'SI' or payment_his_cd = 'CP' or payment_his_cd = 'VW') and cancel_yn = 0 group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ? and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("ssi", $cust_id, $where, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderSeller($order_no)
    {
        $stmt = $this->conn->prepare("SELECT item.seller_id,sel.seller_name,item.order_no,
ord_cd.order_cond_name,sum(item.prod_order_cnt * item.order_pay) as pay,
ord_cd.order_cond_cd,ord.wtid
 from TB_ORDER_ITEM item join TB_ORDER_COND_CD ord_cd
on item.order_cond_cd = ord_cd.order_cond_cd
join TB_SELLER sel on item.seller_id = sel.seller_id
left join TB_ORDER ord on item.order_no = ord.order_no
where item.order_no = ?
and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03')
group by item.seller_id
HAVING pay >= 0");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderSellerCancel($order_no)
    {
        $stmt = $this->conn->prepare("SELECT item.seller_id,sel.seller_name,item.order_no,
ord_cd.order_cond_name,sum(item.prod_order_cnt * -item.order_pay) as pay,
ord_cd.order_cond_cd,ord.wtid
 from TB_ORDER_ITEM item join TB_ORDER_COND_CD ord_cd
on item.order_cond_cd = ord_cd.order_cond_cd
join TB_SELLER sel on item.seller_id = sel.seller_id
left join TB_ORDER ord on item.order_no = ord.order_no
where item.order_no = ?
and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08')
group by item.seller_id
HAVING pay <= 0");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderDetail($order_no,$sellerId,$order_cond_cd,$vw)
    {
      if ($vw == "VW") {
        $stmt = $this->conn->prepare("SELECT prd.prod_name, prd.prod_wgt, ord.prod_order_cnt, prd.sale_unit, prd.fact_name,
          ord.order_no,if(prd.TAXFREE_YN='1',ord.order_pay*ord.prod_order_cnt,ord.order_costpr*ord.prod_order_cnt) as 공급가,
          if(prd.TAXFREE_YN='1',0,(ord.order_pay-ord.order_costpr)*ord.prod_order_cnt) as 부가세,
          ord.order_pay,prd.prod_cont,
          ord.ORDER_DEADLINE_TM,ord.item_memo,prd.prod_cd,ordori.wtid,bc.bn_name
          from TB_PROD prd join TB_ORDER_ITEM ord on prd.prod_cd = ord.prod_cd
          left join TB_ORDER ordori on ordori.order_no = ord.order_no
          left join TB_BN_CD bc on SUBSTRING(ordori.wtid, -2) = bc.bn_cd
          where ord.order_no = ? and ord.seller_id = ? and ord.order_cond_cd = ?");
      }else {
        $stmt = $this->conn->prepare("SELECT prd.prod_name, prd.prod_wgt, ord.prod_order_cnt, prd.sale_unit, prd.fact_name,
          ord.order_no,if(prd.TAXFREE_YN='1',ord.order_pay*ord.prod_order_cnt,ord.order_costpr*ord.prod_order_cnt) as 공급가,
          if(prd.TAXFREE_YN='1',0,(ord.order_pay-ord.order_costpr)*ord.prod_order_cnt) as 부가세,
          ord.order_pay,prd.prod_cont,ord.ORDER_DEADLINE_TM,ord.item_memo,prd.prod_cd
          from TB_PROD prd join TB_ORDER_ITEM ord on prd.prod_cd = ord.prod_cd
          where ord.order_no = ? and seller_id = ? and order_cond_cd = ?");
      }
        $stmt->bind_param("iss", $order_no,$sellerId,$order_cond_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateMSOrderCompleteAdmin($order_cond_cd, $order_no,$sellerId)
    {
        $stmt = $this->conn->prepare("UPDATE TB_ORDER_ITEM set order_cond_cd = ? where order_no = ? and seller_id = ?");
        $stmt->bind_param("sis", $order_cond_cd, $order_no,$sellerId);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
    }

    public function selectMSOrderTrackCancelSeller($order_no)
    {
        $stmt = $this->conn->prepare("SELECT ord_cd.order_cond_name,ts.seller_name,ord.sumpay,ord.seller_id
          from (SELECT *,sum(round(order_pay*prod_order_cnt)) as sumpay from TB_ORDER_ITEM
          where order_no = ? and (order_cond_cd = '04' or order_cond_cd = '05' or order_cond_cd = '08') group by seller_id) ord
          join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join TB_SELLER ts on ts.seller_id = ord.seller_id
          group by ord.seller_id");
        $stmt->bind_param("i", $order_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCancelReturn($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, -sum(round(order_pay*prod_order_cnt)),ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC' group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08') group by 1 order by 1 desc");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCancelReturnWithDays($cust_id, $days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, -sum(round(order_pay*prod_order_cnt)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC'  group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08') and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCancelReturnWithMonth($cust_id, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date,-sum(round(order_pay*prod_order_cnt)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC'  group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and (item.order_cond_cd = '04' or item.order_cond_cd = '05' or item.order_cond_cd = '08') and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("si", $cust_id, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCancel($cust_id,$where)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, -sum(round(order_pay*prod_order_cnt)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC'  group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ?  group by 1 order by 1 desc");
        $stmt->bind_param("ss", $cust_id,$where);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCancelWithDays($cust_id,$where,$days)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, -sum(round(order_pay*prod_order_cnt)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR'  or payment_his_cd = 'CC' or payment_his_cd = 'VC'  group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ?  and item.order_cond_cd = ?  and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by 1 desc");
        $stmt->bind_param("sis", $cust_id,$where,$days);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCancelWithMonth($cust_id,$where, $month)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT ord.order_no, ord.order_date, -sum(round(order_pay*prod_order_cnt)), ord_cd.order_cond_name ,group_concat(DISTINCT item.order_cond_cd) from TB_ORDER ord join TB_ORDER_COND_CD ord_cd on ord.order_cond_cd = ord_cd.order_cond_cd join (select order_no,sum(payment_pr) as  payment_sum from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BC' or payment_his_cd = 'CR' or payment_his_cd = 'CC' or payment_his_cd = 'VC'  group by order_no) as pay on pay.order_no = ord.order_no join TB_ORDER_ITEM item on ord.order_no=item.order_no where ord.cust_id = ? and item.order_cond_cd = ?  and date(ord.order_date) >= date(subdate(now(), interval ? month)) group by 1 order by 1 desc");
        $stmt->bind_param("sis", $cust_id,$where, $month);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectupdateMSitem($order_no,$order_item_no)
    {
        $stmt = $this->conn->prepare("SELECT round(order_pay*prod_order_cnt) as pay from TB_ORDER_ITEM
where order_no = ? and order_item_no = ?");
        $stmt->bind_param("ii",$order_no,$order_item_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectupdateMSitem2($cnt,$order_no,$order_item_no)
    {
        $stmt = $this->conn->prepare("UPDATE TB_ORDER_ITEM set prod_order_cnt = ? where order_no = ? and order_item_no = ?");
        $stmt->bind_param("dii",$cnt,$order_no,$order_item_no);
        if ($stmt->execute()) {
              return UPDATE_COMPLETED;
        } else {
              return UPDATE_FAILED;
        }
    }

    public function selectinsertMSitem2($order_no,$seller_id,$prod_cd,$cnt,$pay,$order_deadline_tm,$costpr,$order_sel_costpr)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_ORDER_ITEM(ORDER_NO,SELLER_ID,PROD_CD,PROD_ORDER_CNT,order_cond_cd,order_pay,order_deadline_tm,order_costpr,order_sel_costpr) VALUES(?,?,?,?,'01',?,?,?,?)");
        $stmt->bind_param("issdiiii",$order_no,$seller_id,$prod_cd,$cnt,$pay,$order_deadline_tm,$costpr,$order_sel_costpr);
        if ($stmt->execute()) {
              return INSERT_COMPLETED;
        } else {
          // $g = mysqli_error($this->conn);//에러메세지출력
          // echo "$g";
              return INSERT_FAILED;
        }
    }

    public function selectupdateMSitem4($price_bln,$deposit,$cust_id,$order_no,$seller_id,$item_update)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT_HIS SET PAYMENT_PR = PAYMENT_PR - ?,DEPOSIT_BLN = ? ,PAYMENT_HIS_MEMO = ? where cust_id = ? and order_no = ? and seller_id = ? and payment_his_cd = 'SC'");
        $stmt->bind_param("iisiss",$price_bln,$deposit,$cust_id,$order_no,$seller_id,$item_update);
        if ($stmt->execute()) {
              return UPDATE_COMPLETED;
        } else {
              return UPDATE_FAILED;
        }
    }

    public function selectupdateMSitem6($price_bln,$deposit,$cust_id,$order_no,$seller_id,$item_update)
    {
        $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT_HIS SET PAYMENT_PR = PAYMENT_PR + ?,DEPOSIT_BLN = ? ,PAYMENT_HIS_MEMO = ? where cust_id = ? and order_no = ? and seller_id = ? and payment_his_cd = 'SI'");
        $stmt->bind_param("iisis",$price_bln,$deposit,$cust_id,$order_no,$seller_id,$item_update);
        if ($stmt->execute()) {
              return UPDATE_COMPLETED;
        } else {
              return UPDATE_FAILED;
        }
    }


    public function insertFavoriteMS($cust_id,$prod_cd,$sellerId)
    {
        // console.log("ㅎㅇ");
        $stmt = $this->conn->prepare("INSERT INTO TB_FAVOR_PROD (cust_id, prod_cd,seller_id) values (?,?,?)");
        $stmt->bind_param("sss", $cust_id, $prod_cd,$sellerId);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function deleteFavoriteMS($cust_id, $prod_cd,$sellerId)
    {
        $stmt = $this->conn->prepare("DELETE from TB_FAVOR_PROD where cust_id = ? and prod_cd = ? and seller_id = ?");
        $stmt->bind_param("sss", $cust_id, $prod_cd,$sellerId);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function productListSearchMS_SG($cust_id,$cust_id1,$cust_id2,$input_main,$like1,$like2,$search_setbox,$cg,$sel,$s_point,$list){
      if ($sel =='ALL') {$sel = '';}
      if ($cg =='ALL') {$cg = '';}
      if (isset($s_point) && isset($list)) {
        $limit  = "limit $s_point,$list";
      }else {
        $limit  = "";
      }
      $orderByStr = '';
      if ($search_setbox == 'price') {
        $orderByStr = ' ORDER BY ori2.PRICE ASC ';
      }elseif ($search_setbox == 'prd.prod_name') {
        $orderByStr = ' ORDER BY ori2.PROD_NAME asc ';
      }
          // order by ori2.PROD_NAME asc
          // order by ori2.PRICE asc
      if($like1 ==""){
        $stmt = $this->conn->prepare("SELECT * from TB_PROD where prod_name=''");
      }else{
        $stmt = $this->conn->prepare("SELECT ori2.stn_cond_cd,ori2.PROD_CD,
                                                	fp.PROD_CD  AS favcd,
                                            	ori2.PROD_NAME,
                                            	ori2.PROD_CONT,
                                            	ori2.PROD_WGT,
                                            	ori2.SALE_UNIT,
                                              ori2.price,
                                                	ori2.PROD_CONT,
                                              ori2.FACT_NAME,
                                                	prod_seller,
                                                  CONCAT(fp.PROD_CD, '_', ori2.seller_id) AS  fav_seller,
                                                  ori2.SELLER_NAME,
                                            	ori2.seller_id,
                                              ori2.ORDER_DEADLINE_TM,
                                              ori2.img,
                                              ori2.POINT_ORDER_YN
                                              	FROM (
                                                		SELECT NO, sc.CUST_ID, prd.PROD_CD,
                                                		CONCAT(prd.PROD_CD, '_',sc.seller_id) AS prod_seller,
                                              	IF(INSTR('$this->JinhyunPom', sp.seller_id),
                                                			IF(prd.taxfree_yn = 0, ROUND(sr.seller_prod_price * 1.1), sr.seller_prod_price),
                                                		     ( IF( prd.prod_cd = discunt.prod_cd,
                                                			IF(prd.taxfree_yn = 0 , ROUND(ROUND(
                                                  		 ROUND(( sr.seller_prod_price / 0.95 ) /
                                                				     ( ( 100 - sc.margin_rate ) * 0.01 ), -1) * ( (
                                                  		 100 - discunt.discount_rate ) * 0.01 ), -1) * 1.1)
                                                  		 , ROUND(
                                                  		   ROUND(( sr.seller_prod_price / 0.95 ) /
                                                				 ( ( 100 - sc.margin_rate ) * 0.01 ), -1) * ( (
                                                			   100 - discunt.discount_rate ) * 0.01 ), -1)), IF( prd.taxfree_yn = 0 , ROUND(
                                                		       ROUND(ROUND(( sr.seller_prod_price / 0.95 ) / ( ( 100 - sc.margin_rate ) * 0.01 ), -1),
                                                			 -1) * 1.1) , ROUND( ROUND(( sr.seller_prod_price / 0.95 ) / ( ( 100 - sc.margin_rate ) * 0.01 ), -1))))
                                                     )
                                                 ) AS  price,
                                                		prd.PROD_NAME, prd.PROD_CONT, prd.PROD_WGT, prd.SALE_UNIT, prd.FACT_NAME,
                                                		sc.seller_id, seller.SELLER_NAME, prd.img, sr.ORDER_DEADLINE_TM, sr.POINT_ORDER_YN,
                                                    prd.stn_cond_cd
                                                		FROM (
                                                			SELECT  @ROWNUM:=@ROWNUM+1 AS NO,  tb.PROD_CD FROM
                                                				( SELECT DISTINCT( tb.PROD_CD)  FROM (
                                                					SELECT PROD_CD FROM TB_PROD WHERE PROD_NAME LIKE '$input_main%' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE PROD_CONT  LIKE '$input_main%' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE FACT_NAME  LIKE '$input_main%' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE PROD_NAME LIKE '%$input_main' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE PROD_CONT  LIKE '%$input_main' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE FACT_NAME  LIKE '%$input_main' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE PROD_NAME LIKE '%$input_main%' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE PROD_CONT  LIKE '%$input_main%' UNION

                                                					SELECT PROD_CD FROM TB_PROD WHERE FACT_NAME  LIKE '%$input_main%' UNION
                                                          SELECT PROD_CD FROM TB_PROD WHERE PROD_NAME LIKE '%$input_main%' OR PROD_NAME REGEXP
                                                          	(SELECT REPLACE(GROUP_CONCAT(KEYWORD), ',', '|') AS NAME
                                                          	FROM  TB_PROD_SEARCH_MATCH
                                                          	WHERE KEYWORD_SEARCH = '$input_main')) tb
                                                				) tb ,  (SELECT @rownum:=0) TMP )ori
                                                			LEFT OUTER JOIN TB_PROD prd ON prd.PROD_CD = ori.PROD_CD
                                            		LEFT OUTER JOIN TB_SELLER_PROD_CD sp ON ori.PROD_CD = sp.PROD_CD
                                            		LEFT OUTER JOIN TB_SELLER_BY_CUST sc ON sp.SELLER_ID = sc.SELLER_ID
                                            		LEFT OUTER JOIN TB_SELLER_PROD_PRICE sr ON sr.SELLER_PROD_CD = sp.SELLER_PROD_CD and sc.SELLER_ID = sr.SELLER_ID
                                            		LEFT OUTER JOIN TB_PROD_DISCOUNT discunt ON discunt.PROD_CD = ori.PROD_CD AND discunt.CUST_ID = sc.CUST_ID AND discunt.SELLER_ID = sc.seller_id
                                                		LEFT OUTER JOIN TB_SELLER seller ON seller.seller_id = sr.seller_id
                                            		WHERE sc.CUST_ID = ?
                                            		AND sc.seller_id LIKE '%$sel%'
                                            		AND sr.SELLER_PROD_PRICE IS NOT NULL
                                            		ORDER BY NO ASC ) ori2

                                                	LEFT OUTER JOIN TB_FAVOR_PROD fp ON fp.cust_id = ori2.cust_id AND fp.seller_id = ori2.seller_id AND fp.PROD_CD = ori2.PROD_CD
                                            	WHERE ori2.PROD_CD LIKE '%$cg%'
                                              AND NOT ori2.PROD_CD LIKE '%E%'
                                          $orderByStr
                                        	$limit");
      }
      $stmt->bind_param("s",$cust_id);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return PRODUCT_NOT_EXIST;
      }
    }
    public function productListSearchMS($cust_id,$cust_id1,$cust_id2,$input_main,$like1,$like2,$search_setbox,$cg,$sel,$s_point,$list)
    {

      if (isset($s_point) && isset($list)) {
              $limit  = "limit $s_point,$list";
      }else {
              $limit  = "";
      }
      if ($cg == "ALL") {
        $cg_where ="";
      }else {
        // $cg_where =" and class_cd.class_cd = '$cg' ";
        $cg_where =" and prd.class_cd like '%$cg%'";
      }
      if ($sel =='ALL') {
        $sel_where ="";
      }else {
        $sel_where =" and prd_sel.seller_id ='$sel' ";
      }
      // $all_by = "(CASE prd.prod_wgt * 1 WHEN prd.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),prd.prod_wgt *1 asc";
      $all_by = "prd.prod_name asc,prd.prod_cont asc,prd.fact_name asc,prd.prod_wgt asc,price asc";
      // echo "$cust_id,$cust_id1,$cust_id2,$input_main,$like1,$like2,$search_setbox";
       // echo "$cust_id,$cust_id1,$cust_id2,$input_main,$like1,$like2,$search_setbox";
        if($like1 ==""){
          $stmt = $this->conn->prepare("SELECT * from TB_PROD where prod_name=''");
        }else{
          // $stmt = $this->conn->prepare("SELECT prd.prod_cd, fav.prod_cd favcd,prd.prod_name,
          //    prd.prod_cont, prd.prod_wgt, prd.sale_unit,
          //    if(INSTR('$this->JinhyunPom',prd_sel.seller_id),if(prd.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
          //    if(prd.prod_cd = discunt.prod_cd
          //       ,if(prd.TAXFREE_YN = 0
          //         ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
          //         ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
          //       ,if(prd.TAXFREE_YN = 0
          //         ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
          //         ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)))))) as price,prd.prod_cont,
          //     prd.fact_name,concat(prd_sel.prod_cd,'_',prd_sel.seller_id)  prod_seller ,
          //     concat(fav.prod_cd,'_',fav.seller_id) fav_seller ,sel_activ.seller_name,prd_sel.seller_id
          //     ,sel_price.ORDER_DEADLINE_TM,prd.img,sel_price.point_order_yn
          //     from TB_PROD prd join TB_SELLER_PROD_CD prd_sel on prd.prod_cd = prd_sel.prod_cd
          //     left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav
          //     on concat(prd_sel.prod_cd,'_',prd_sel.seller_id) = concat(fav.prod_cd,'_',fav.seller_id)
          //     join TB_SELLER sel_activ on prd_sel.seller_id = sel_activ.seller_id
          //     join TB_SELLER_PROD_PRICE sel_price on concat(prd_sel .seller_id,'_',prd_sel.seller_prod_cd) = concat(sel_price.seller_id,'_',sel_price.seller_prod_cd)
          //     join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel_activ.seller_id = selcust.seller_id
          //     join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt on sel_activ.seller_id=discunt.seller_id and prd.prod_cd=discunt.prod_cd
          //     left join TB_CLASS_CD class_cd on substring(prd.CLASS_CD,1,1) = class_cd.class_cd
          //     where prd_sel.SELLER_PROD_CD = sel_price.SELLER_PROD_CD and sel_activ.activ_yn = 1
          //     and (prd.prod_name like concat('%','$input_main','%') or prd.prod_cont like concat('%',?,'%'))
          //     $cg_where
          //     $sel_where
          //     order by  case   WHEN $search_setbox LIKE ? THEN 0 ELSE 1 END ,$search_setbox ,$all_by, prd.prod_name asc $limit");
          $stmt = $this->conn->prepare("SELECT * from ( SELECT prd.prod_cd,
       fav.prod_cd                                                       favcd,
       prd.prod_name,
       prd.prod_cont,
       prd.prod_wgt,
       prd.sale_unit,
       IF(Instr('$this->JinhyunPom', prd_sel.seller_id),
       IF(prd.taxfree_yn =
       0, Round(sel_price.seller_prod_price * 1.1),
                                     sel_price.seller_prod_price), ( IF(
       prd.prod_cd = discunt.prod_cd, IF(prd.taxfree_yn = 0
       , Round(Round(
                 Round(( sel_price.seller_prod_price / 0.95 ) /
                             ( ( 100 - selcust.margin_rate ) * 0.01 ), -1) * ( (
                 100 - discunt.discount_rate ) * 0.01 ), -1) * 1.1)
                 , Round(
                   Round(( sel_price.seller_prod_price / 0.95 ) /
                         ( ( 100 - selcust.margin_rate ) * 0.01 ), -1) * ( (
                   100 - discunt.discount_rate ) * 0.01 ), -1)), IF(
       prd.taxfree_yn = 0
                                                                 , Round(
       Round(Round(( sel_price.seller_prod_price / 0.95 ) /
                                 ( ( 100 - selcust.margin_rate ) * 0.01 ), -1),
         -1) * 1.1)
       , Round(
         Round(( sel_price.seller_prod_price / 0.95 ) /
                     ( ( 100 - selcust.margin_rate ) * 0.01 ), -1)))) )) AS
       price,
       prd.prod_cont,
       prd.fact_name,
       Concat(prd_sel.prod_cd, '_', prd_sel.seller_id)
       prod_seller,
       Concat(fav.prod_cd, '_', fav.seller_id)
       fav_seller,
       sel_activ.seller_name,
       prd_sel.seller_id,
       sel_price.order_deadline_tm,
       prd.img,
       sel_price.point_order_yn
FROM   TB_PROD prd
       JOIN TB_SELLER_PROD_CD prd_sel
         ON prd.prod_cd = prd_sel.prod_cd
       LEFT JOIN (SELECT prod_cd,
                         seller_id
                  FROM   TB_FAVOR_PROD
                  WHERE  cust_id = ?) fav
              ON Concat(prd_sel.prod_cd, '_', prd_sel.seller_id) =
                 Concat(fav.prod_cd, '_', fav.seller_id)
       JOIN TB_SELLER sel_activ
         ON prd_sel.seller_id = sel_activ.seller_id
       JOIN TB_SELLER_PROD_PRICE sel_price
         ON Concat(prd_sel .seller_id, '_', prd_sel.seller_prod_cd) =
            Concat(
            sel_price.seller_id, '_', sel_price.seller_prod_cd)
       JOIN (SELECT *
             FROM   TB_SELLER_BY_CUST
             WHERE  cust_id = ?) selcust
         ON sel_activ.seller_id = selcust.seller_id
       JOIN (SELECT *
             FROM   TB_PROD_DISCOUNT
             WHERE  cust_id = ?) discunt
         ON sel_activ.seller_id = discunt.seller_id
            AND prd.prod_cd = discunt.prod_cd
       LEFT JOIN TB_CLASS_CD class_cd
              ON Substring(prd.class_cd, 1, 1) = class_cd.class_cd
WHERE  prd_sel.seller_prod_cd = sel_price.seller_prod_cd
       AND sel_activ.activ_yn = 1
       AND ( prd.prod_name REGEXP (SELECT REPLACE(Group_concat(keyword), ',',
                                          '|') AS NAME
                                   FROM   TB_PROD_SEARCH_KEYWORD
                                   WHERE  keyword_search LIKE concat('%','$input_main','%'))
              OR prd.prod_cont LIKE Concat('%',?,'%') or prd.prod_name LIKE concat('%','$input_main','%'))
              $cg_where
              $sel_where
              $limit )tb");
          $stmt->bind_param("sssss",$cust_id,$cust_id1,$cust_id2,$like1,$like2);
          // order by  case   WHEN $search_setbox LIKE ? THEN 0 ELSE 1 END ,$search_setbox ,$all_by, prd.prod_name asc $limit )tb");
          //concat(sel_activ.seller_id,'_',prd.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }
    public function cookiepriceMS($cust_id,$cust_id1,$prod_cd,$sellerId)
    {
        $stmt = $this->conn->prepare("SELECT if(prd.prod_cd = discunt.prod_cd,if(prd.TAXFREE_YN = 0,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01)*1.1)
,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
,if(prd.TAXFREE_YN = 0,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*1.1)
,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)))) as price
          from TB_SELLER_PROD_CD prd_sel join TB_SELLER_PROD_PRICE sel_price
          on concat(prd_sel .seller_id,'_',prd_sel.seller_prod_cd) = concat(sel_price.seller_id,'_',sel_price.seller_prod_cd)
          join TB_PROD prd on prd.prod_cd = prd_sel.prod_cd
          join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on prd_sel.seller_id = selcust.seller_id
          left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt
          on prd_sel.seller_iddiscunt.seller_id and prd.prod_cd=discunt.prod_cd
           where prd_sel.prod_cd= ? and sel_price.seller_id = ?");
           //concat(prd_sel.seller_id,'_',prd.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->bind_param("ssss",$cust_id,$cust_id1,$prod_cd,$sellerId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return PRODUCT_NOT_EXIST;
        }
    }

    public function selectMSPurchaseTotal($cust_id, $month)
    {

      if($month == 0){
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)),sum(item.prod_order_cnt),
        sum(item.prod_order_cnt*item.order_pay-item.coupon_price) as total_pay,IFNULL(max(ord.reg_date),'0')
        from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no
        where ord.cust_id = ? and item.order_cond_cd = '03' order by total_pay desc");
        $stmt->bind_param("s", $cust_id);
      } else if($month == 1){
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)),sum(item.prod_order_cnt),
        sum(item.prod_order_cnt*item.order_pay-item.coupon_price) as total_pay,IFNULL(max(ord.reg_date),'0')
        from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no
        where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW())) and item.order_cond_cd = '03' order by total_pay desc");
        $stmt->bind_param("si", $cust_id, $month);
      }else {
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)),sum(item.prod_order_cnt),
        sum(item.prod_order_cnt*item.order_pay-item.coupon_price) as total_pay,IFNULL(max(ord.reg_date),'0')
        from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no
        where ord.cust_id = ? and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month)) and item.order_cond_cd = '03' order by total_pay desc");
        $stmt->bind_param("si", $cust_id, $month);
      }
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }

    }
    public function selectMSPurchaseOrdercnt($cust_id, $month)
    {

      if($month == 0){
        $stmt = $this->conn->prepare("SELECT count(ord.order_no) from TB_ORDER ord where ord.cust_id = ?");
        $stmt->bind_param("s", $cust_id);
      } else if($month == 1){
        $stmt = $this->conn->prepare("SELECT count(ord.order_no) from TB_ORDER ord where ord.cust_id = ? and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW()))");
        $stmt->bind_param("si", $cust_id, $month);
      }else {
        $stmt = $this->conn->prepare("SELECT count(ord.order_no) from TB_ORDER ord where ord.cust_id = ? and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month))");
        $stmt->bind_param("si", $cust_id, $month);
      }
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }

    }
   //  ajax purchaseBottom.php
    public function selectMSPurchaseTotalDetail($cust_id, $selectTypePurchase, $month,$taxfree_yn)
    {
      // echo "$cust_id, $selectTypePurchase, $month,$taxfree_yn";
        if ($taxfree_yn == '2') {
          $taxfree_yn_where = "";
        }else {
          $taxfree_yn_where = "and prd.taxfree_yn  like ? ";
        }
          // echo "$cust_id, $selectTypePurchase, $month";
      if($month == 0) {//전체
        if ($selectTypePurchase == 1) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.prod_cont,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ? and item.order_cond_cd = '03' $taxfree_yn_where group by 3 order by total_pay desc");
          if ($taxfree_yn == '2') {
              $stmt->bind_param("s", $cust_id);
          }else {
              $stmt->bind_param("ss", $cust_id,$taxfree_yn);
          }
        } else if ($selectTypePurchase == 0) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.prod_cont,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ? and item.order_cond_cd = '03' $taxfree_yn_where group by 3 order by count_num desc");
          if ($taxfree_yn == '2') {
              $stmt->bind_param("s", $cust_id);
          }else {
              $stmt->bind_param("ss", $cust_id,$taxfree_yn);
          }
        }
      } else if($month == 1){//이번달
        if ($selectTypePurchase == 1) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.prod_cont,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW())) and item.order_cond_cd = '03' $taxfree_yn_where group by 3 order by total_pay desc");
          if ($taxfree_yn == '2') {
              $stmt->bind_param("si", $cust_id, $month);
          }else {
              $stmt->bind_param("sis", $cust_id, $month,$taxfree_yn);
          }
        } else if ($selectTypePurchase == 0) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.prod_cont,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW())) and item.order_cond_cd = '03' $taxfree_yn_where  group by 3 order by count_num desc");
          if ($taxfree_yn == '2') {
              $stmt->bind_param("si", $cust_id, $month);
          }else {
              $stmt->bind_param("sis", $cust_id, $month,$taxfree_yn);
          }
        }
      }else {//저번달
        if ($selectTypePurchase == 1) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month)) and item.order_cond_cd = '03' $taxfree_yn_where  group by 3 order by total_pay desc");
          if ($taxfree_yn == '2') {
              $stmt->bind_param("si", $cust_id, $month);
          }else {
              $stmt->bind_param("sis", $cust_id, $month,$taxfree_yn);
          }
        } else if ($selectTypePurchase == 0) {
          $stmt = $this->conn->prepare("SELECT sum(item.prod_order_cnt) as count_num,item.order_no,item.prod_cd,prd.prod_name,prd.origin_name,prd.prod_wgt,prd.sale_unit,prd.fact_name,sum(item.prod_order_cnt*item.order_pay) as total_pay from TB_ORDER ord join TB_ORDER_ITEM item on ord.order_no = item.order_no join TB_PROD prd on item.prod_cd = prd.prod_cd where ord.cust_id = ?  and subdate((select LAST_DAY(NOW() - interval ? month)), interval -1 day) <=  date(ord.order_date) and date(ord.order_date) <= date(last_day(NOW() - interval 1 month)) and item.order_cond_cd = '03' $taxfree_yn_where group by 3 order by count_num desc");
          if ($taxfree_yn == '2') {
              $stmt->bind_param("si", $cust_id, $month);
          }else {
              $stmt->bind_param("sis", $cust_id, $month,$taxfree_yn);
          }
        }
      }
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }

    }

    public function selectMSProductDetail($cust_id,$cust_id2,$prod_cd,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT prd.prod_name, prd.prod_cont, prd.prod_wgt,
prd.sale_unit,
if(INSTR('$this->JinhyunPom',prd_sel.seller_id),if(prd.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
if(prd.prod_cd = discunt.prod_cd
  ,if(prd.TAXFREE_YN = 0,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
  ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
  ,if(prd.TAXFREE_YN = 0,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
  ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))  as price,prd.fact_name,stn.stn_cond_name,sel.seller_name,
concat(prd.prod_cd,'_',prd_sel.seller_id) prod_seller,prd.TAXFREE_YN,sel_price.ORDER_DEADLINE_TM,sel_price.seller_prod_name,prd.img,sel_price.point_order_yn
from TB_PROD prd join TB_SELLER_PROD_CD prd_sel
 on prd.prod_cd = prd_sel.prod_cd  join TB_SELLER_PROD_PRICE sel_price on
concat(prd_sel.seller_id,'_',prd_sel.seller_prod_cd) = concat(sel_price.seller_id,'_',sel_price.seller_prod_cd)
join TB_STN_COND stn
  on prd.stn_cond_cd = stn.stn_cond_cd join TB_SELLER sel
   on prd_sel.seller_id = sel.seller_id
 join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel.seller_id = selcust.seller_id
left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) discunt on sel.seller_id=discunt.seller_id and prd.prod_cd=discunt.prod_cd
 where prd.prod_cd = ? and prd_sel.seller_id = ?");
 //concat(sel.seller_id,'_',prd.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
        $stmt->bind_param("ssss",$cust_id,$cust_id2,$prod_cd,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSFavoriteProduct($cust_id, $prod_cd,$seller_id)
    {
        $stmt = $this->conn->prepare("SELECT concat(prod_cd,'_',seller_id)  prod_seller from TB_FAVOR_PROD where cust_id = ? and prod_cd = ? and seller_id =?");
        $stmt->bind_param("sss", $cust_id, $prod_cd,$seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectMSCirculationNoticeSeller($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT sel.seller_name,cust.seller_id from TB_SELLER_BY_CUST cust join TB_SELLER sel on cust.seller_id = sel.seller_id where cust.cust_id = ? and sel.ACTIV_YN = 1");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSCirculationNoticeSellerNoti($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT sel.seller_name,cust.seller_id,sel.tel_no from TB_SELLER_BY_CUST cust join TB_SELLER sel on cust.seller_id = sel.seller_id where cust.cust_id = ? and sel.ACTIV_YN = 1");
        $stmt->bind_param("s", $cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function countMSBeforeShippingAdmin($seller_id)
    {
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)) from TB_ORDER ord join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no where ordi.seller_id = ? and ordi.order_cond_cd = '01'");
        $stmt->bind_param("s", $seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function countMSShippingAdmin($seller_id)
    {
        $stmt = $this->conn->prepare("SELECT count(DISTINCT(ord.order_no)) from TB_ORDER ord join TB_ORDER_ITEM ordi on ord.order_no = ordi.order_no where ordi.seller_id = ? and ordi.order_cond_cd = '02'");
        $stmt->bind_param("s", $seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectCustName($seller_id)
    {
        $stmt = $this->conn->prepare("SELECT cust.BUSINESS_NAME,cust.cust_id FROM TB_CUST cust join TB_ORDER ord  on ord.cust_id = cust.cust_id join TB_ORDER_ITEM item on ord.order_no = item.order_no where item.seller_id = ? group by ord.cust_id");
        $stmt->bind_param("s", $seller_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackAdmin($seller_id_sub,$seller_id,$select_type,$search_textfield)
    {
        $str = "SELECT pay.order_no,pay.payment_date,item.pay,ocn.order_cond_name,cst.business_name,cst.cust_id,ocn.order_cond_cd
        from TB_CUST_PAYMENT_HIS pay join (select *,sum(if(ip.taxfree_yn=1,ip.order_sel_costpr,round(ip.order_sel_costpr*1.1)) * ip.prod_order_cnt) as pay  from
        (SELECT i.order_cond_cd,i.seller_id,i.order_no,i.order_item_no,i.prod_cd,i.prod_order_cnt,i.order_sel_costpr,i.order_deadline_tm,p.taxfree_yn from TB_ORDER_ITEM i left join TB_PROD p on i.PROD_CD = p.PROD_CD) ip
        where seller_id = ? group by order_no) item
        on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id)
        join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd
        join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id";
        if($select_type == '전체'){
          if(!isset($search_textfield) || $search_textfield == ""){
            $stmt = $this->conn->prepare("$str  where pay.seller_id  = ? group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '입금대기,취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("ss", $seller_id_sub,$seller_id);
          }else{
            $stmt = $this->conn->prepare("$str  where pay.seller_id  = ? and cst.business_name like CONCAT('%',?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("sss", $seller_id_sub,$seller_id,$search_textfield);
          }
        }else{
          if(!isset($search_textfield) || $search_textfield == ""){
            $stmt = $this->conn->prepare("$str  where pay.seller_id  = ? and ocn.order_cond_name = ? group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '입금대기,취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("sss", $seller_id_sub,$seller_id , $select_type);
          }else{
            $stmt = $this->conn->prepare("$str  where pay.seller_id  = ? and ocn.order_cond_name = ? and cst.business_name like CONCAT('%', ?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
            $stmt->bind_param("ssss", $seller_id_sub,$seller_id , $select_type,$search_textfield);
          }
        }

        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function selectMSOrderTrackAdminWithDays($seller_id_sub,$seller_id,$select_type_day,$select_type,$search_textfield)
    {
      $str = "SELECT pay.order_no,pay.payment_date,item.pay,ocn.order_cond_name,cst.business_name,cst.cust_id,ocn.order_cond_cd
        from TB_CUST_PAYMENT_HIS pay join (select *,sum(if(ip.taxfree_yn=1,ip.order_sel_costpr,round(ip.order_sel_costpr*1.1)) * ip.prod_order_cnt) as pay from
        (SELECT i.order_cond_cd,i.seller_id,i.order_no,i.order_item_no,i.prod_cd,i.prod_order_cnt,i.order_sel_costpr,i.order_deadline_tm,p.taxfree_yn from TB_ORDER_ITEM i left join TB_PROD p on i.PROD_CD = p.PROD_CD) ip
        where seller_id = ? group by order_no) item
        on concat(pay.order_no,'_',pay.seller_id) = concat(item.order_no,'_',item.seller_id)
        join TB_ORDER_COND_CD ocn on item.order_cond_cd = ocn.order_cond_cd
        join TB_ORDER ord on pay.order_no = ord.order_no join TB_CUST cst on ord.cust_id = cst.cust_id";
      if($select_type == '전체'){
        if(!isset($search_textfield) || $search_textfield == ""){
          $stmt = $this->conn->prepare("$str where pay.seller_id  = ?  and date(ord.order_date) >= date(subdate(now(), interval ? day)) group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '입금대기,취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssi", $seller_id_sub,$seller_id, $select_type_day);
        }else{
          $stmt = $this->conn->prepare("$str  where pay.seller_id  = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) and cst.business_name like CONCAT('%',?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '입금대기,취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssis", $seller_id_sub,$seller_id, $select_type_day,$search_textfield);
        }
      }else{
        if(!isset($search_textfield) || $search_textfield == ""){
          $stmt = $this->conn->prepare("$str  where pay.seller_id  = ?  and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ocn.order_cond_name = ? group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssis", $seller_id_sub,$seller_id, $select_type_day , $select_type);
        }else{
          $stmt = $this->conn->prepare("$str  where pay.seller_id  = ? and date(ord.order_date) >= date(subdate(now(), interval ? day)) and ocn.order_cond_name = ? and cst.business_name like CONCAT('%', ?, '%') group by 1 order by pay.payment_date desc, find_in_set(ocn.order_cond_name, '취소접수,출고전,배송중,배송완료,반품완료') asc ");
          $stmt->bind_param("ssiss", $seller_id_sub,$seller_id, $select_type_day , $select_type,$search_textfield);
        }
      }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function CustList()
    {
        $stmt = $this->conn->prepare("SELECT cust.CUST_ID , cust.BUSINESS_NAME from TB_CUST cust join TB_CUST_PAYMENT custpay on cust.cust_id = custpay.cust_id");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function AllCustList()
    {
        $stmt = $this->conn->prepare("SELECT cust.CUST_ID , BUSINESS_NAME from TB_CUST cust left join TB_SELLER sel on cust.cust_id = sel.seller_id where sel.seller_id is null and cust.cust_id !='deposit'");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function CustListPayment($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT CUST_ACCN_BN_NAME, CUST_ACCN_NO,
          DEPOSIT_BLN FROM TB_CUST_PAYMENT WHERE cust_id = ?");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function CustListPaymentCredit($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT pay.CUST_ACCN_BN_NAME,IFNULL(pay.CUST_DEPOSIT_ACCN_NO,pay.CUST_ACCN_NO),
          pay.DEPOSIT_BLN,pay.credit_limit,pay.CONTRACT,pay.BUSINESS_REGISTRATION,pay.GUARANTEE_INSURANCE_SECURITIES,pay.SETTLEMENT_DT
          ,cust.ADDR_CONT,cust.ADDR_CD,cust.reg_date,cust.TEL_NO,cust.OWNER_NAME,pay.status,pay.trade,cust.email,cust.SALES_INOUT,cust.SALES_TYPE
          FROM TB_CUST_PAYMENT pay join TB_CUST cust on pay.cust_id = cust.cust_id WHERE pay.cust_id = ?");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }
    public function insertOrderPay($cust_id)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_ORDER (order_date, cust_id, reg_date, memo,order_cond_cd) values (now(), ?, now(),'딜리버리랩 입금','06')");
        $stmt->bind_param("s", $cust_id);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    private function isVacctExist($vacct)
    // public function isVacctExist($vacct)
    {
        $stmt = $this->conn->prepare("SELECT CUST_ID
          FROM TB_CUST_PAYMENT WHERE CUST_DEPOSIT_ACCN_NO = ?
          and CUST_DEPOSIT_ACCN_NO is not null");
        $stmt->bind_param("s", $vacct);
        $stmt->execute();
        $stmt->store_result();
        // return $stmt->num_rows > 0;
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }
    public function selectVacctInsertOrderPay($vacct,$amt_input,$wtid)
    {
      // $deposit_insert = $amt_input;
      $isVacctExist = $this->isVacctExist($vacct);//계좌번호검색
      if ($isVacctExist == SELECT_FAILED) {//검색실패
        return SELECT_FAILED;
      }else {//검색성공
        $isVacctExist -> bind_result($cust_id);
        while ($isVacctExist->fetch()) {//반환되는 아이디값으로 검색.
          $account = $this->UserAccountLimit($cust_id);
          if ($account == ACCOUNT_NOT_EXIST) {//계좌정보조회실패
            return SELECT_FAILED;
            // 입금오류발생 재시도해주세요.
          } else {//계좌정보조회 성공
            $account->bind_result($cust_accn_bn_name_select,$cust_accn_no_select,
            $deposit_bln_select,$credit_limit_select,$benefit_yn_select,$activ_yn,
            $guarantee_insurance_securities,$business_registration,$contract,
            $settlement_dt,$status,$business_name);
            while($account->fetch()) {//정보조회후 추가된 insert 정보 확인.
              $stmt = $this->conn->prepare("INSERT INTO TB_ORDER
                (order_date, cust_id, reg_date, memo,order_cond_cd,wtid)
                values (now(), ?, now(),'자동 입금','06',?)");
              $stmt->bind_param("ss", $cust_id,$wtid);
              if ($stmt->execute()) {//입금 오더번호 생성 완료.
                $result_order_no = $this->selectOrderBD($cust_id);//생성번호조회
                $result_order_no -> bind_result($order_no,$sales_tel_no);
                while($result_order_no->fetch()) {
                  $bln = (int)$deposit_bln_select;//통장잔액형변환.
                  $insert_deposi = (int)$amt_input;//계산할 입금액형변환
                  $insert_deposi_select = (int)$amt_input * -1;//기록할 입금액형변환
                  $final_bln =  $bln+$insert_deposi;//최종통장잔액

                  $result_his = $this->insertCancelHistory($cust_id,$order_no,$insert_deposi_select,$final_bln,"orderhero","BD","자동입금","$cust_id");
                  if($result_his == INSERT_COMPLETED){//입금기록 성공
                    $result_bal = $this->updateBalance($final_bln,$cust_id);//통장잔액업데이트
                    if ($result_bal == UPDATE_COMPLETED) {
                      return $cust_id;//*최종반환값*
                    }else {
                      return UPDATE_FAILED;
                    }
                  }else {
                    return INSERT_FAILED;
                  }
                }
              } else {
                  return INSERT_FAILED;
              }
            }
          }
        }
      }
      // if (!$this->isVacctExist($vacct)) {//INSERT
      //   $stmt = $this->conn->prepare("INSERT INTO TB_ORDER
      //     (order_date, cust_id, reg_date, memo,order_cond_cd)
      //     values (now(), ?, now(),'딜리버리랩 입금','06')");
      //   $stmt->bind_param("s", $vacct);
      //   if ($stmt->execute()) {
      //       return INSERT_COMPLETED;
      //   } else {
      //       return INSERT_FAILED;
      //   }
      // } else {
      //       return INSERT_NOTROW;
      // }

    }

    public function selectMS_prod_cd_user($seller_id,$class,$class_cd,$class_cd_detail,$option,$search_textfield,$cust,$row_num,$add_input_text,$s_point,$list,$tm)
    {

      if (isset($s_point) &&isset($list)) {
              $limit  = "limit $s_point,$list";
      }else {
              $limit  = "";
      }
      if (isset($tm)) {
              $tmWhere  = " and p_pe.ORDER_DEADLINE_TM like '$tm' ";
      }else {
              $tmWhere  = "";
      }

      if ($option == "ALL") {
        $option = "prod_name";
        $add_option =" and $option like concat('%',?,'%') and
        (prod_name like concat('%','$search_textfield','%')  or
        dc.PROD_CD like concat('%','$search_textfield','%')  or
        prod_cont like concat('%','$search_textfield','%')  or
        prod_wgt like concat('%','$search_textfield','%')  or
        fact_name like concat('%','$search_textfield','%') or
        p_pe.seller_prod_cd like concat('%','$search_textfield','%')) ";
      }else if($option == "dc.prod_cd"){
        $add_option = "and ($option like concat('%',?,'%') or
        p_pe.seller_prod_cd like concat('%','$search_textfield','%')) ";
      }else {
        $add_option = " and $option like concat('%',?,'%') ";
      }
      // echo "auto : $s_point ::::::::::::: $list";
      // echo "$seller_id,$class,$class_cd,$class_cd_detail,$option,$search_textfield,$cust,$row_num,$add_input_text";
      // echo "$seller_id,$class,$class_cd,$class_cd_detail,$option,$search_textfield,$cust,$row_num,$add_input_text";
      if ($add_input_text != "") {
        $add_text="and (pt.prod_name like concat('%','$add_input_text','%') or pt.prod_cont like concat('%','$add_input_text','%') or pt.fact_name like concat('%','$add_input_text','%') or pt.prod_wgt like concat('%','$add_input_text','%'))";
      }else {
        $add_text ="";
        // echo "없음";
      }

      if ($seller_id == "ALL") {
        $seller_id_Where = "";
      }else {
        $seller_id_Where = "where seller_id = '$seller_id'";
      }
      // echo "$seller_id_Where";
      //round(round(p_pe.SELLER_PROD_PRICE/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-dc.discount_rate)*0.01),0)
      $str = "SELECT dc.PROD_CD,CLASS_CD,CLASS_DETAIL_CD,
      PROD_NAME,PROD_CONT,PROD_WGT,FACT_NAME,
      p_pe.ORDER_DEADLINE_TM,dc.seller_id,dc.discount_rate,sel.seller_name,p_cd.seller_prod_cd,p_pe.SELLER_PROD_PRICE
      ,
      if(INSTR('$this->JinhyunPom',p_cd.seller_id),p_pe.SELLER_PROD_PRICE,(
      round(round((p_pe.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-dc.discount_rate)*0.01),-1)))  as price,pt.TAXFREE_YN,fav.prod_cd FROM (select class_cd,class_detail_cd,prod_name,prod_cont,prod_wgt,fact_name,order_deadline_tm,prod_cd,TAXFREE_YN from TB_PROD) pt
      inner join (SELECT * from TB_PROD_DISCOUNT where cust_id = '$cust') dc on pt.prod_cd = dc.prod_cd inner join (select seller_id,seller_name from  TB_SELLER $seller_id_Where) sel on dc.seller_id = sel.seller_id
       inner join TB_SELLER_PROD_CD p_cd on dc.prod_cd=p_cd.prod_cd and dc.seller_id=p_cd.seller_id inner join TB_SELLER_PROD_PRICE p_pe
      on p_cd.seller_prod_cd=p_pe.seller_prod_cd and dc.seller_id=p_pe.seller_id
      join (SELECT * from TB_SELLER_BY_CUST where cust_id = '$cust')
      selcust on dc.cust_id=selcust.cust_id and dc.seller_id=selcust.seller_id
      left join (SELECT * from TB_FAVOR_PROD where cust_id = '$cust') fav on dc.prod_cd=fav.prod_cd and dc.seller_id=fav.seller_id
      where dc.prod_cd=p_cd.prod_cd and p_cd.seller_prod_cd = p_pe.seller_prod_cd $add_text $tmWhere";
        // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield";
        $echo_text = explode(" ",$search_textfield);
        $echo_text_name = $echo_text[0];
        $echo_text_cont = $echo_text[1];
        // echo "$echo_text_name / $echo_text_cont";
        if ($class == "ALL"&& $search_textfield =="") {
          //echo "전체";
          $stmt = $this->conn->prepare("$str and dc.cust_id =? order by dc.discount_rate $row_num,fav.prod_cd $row_num $limit");
          $stmt->bind_param("s",$cust);
        }else if ($class != "ALL" && $search_textfield =="") {
          //echo "분류 키워드없음";
          if ($class_cd == "ALL") {
            // echo "  1차전체";
            $stmt = $this->conn->prepare("$str and dc.PROD_CD like concat('%',?,'%') and dc.cust_id =? order by dc.discount_rate $row_num,fav.prod_cd $row_num $limit");
            $stmt->bind_param("ss",$class,$cust);
          }else {
            // echo "  1차분류";
            if ($class_cd_detail =="ALL") {
              //echo "  2차전체";
              $stmt = $this->conn->prepare("$str
               and dc.PROD_CD like concat('%',?,'%') and class_cd = ? and dc.cust_id =? order by dc.discount_rate $row_num,fav.prod_cd $row_num $limit");
              $stmt->bind_param("sss",$class,$class_cd,$cust);
            }else {
              //echo "  2차분류";
              $stmt = $this->conn->prepare("$str
               and dc.PROD_CD like concat('%',?,'%') and class_cd = ? and class_detail_cd = ? and dc.cust_id =? order by dc.discount_rate $row_num,fav.prod_cd $row_num $limit");
              $stmt->bind_param("ssss",$class,$class_cd,$class_cd_detail,$cust);
            }
          }
        }else if ($class == "ALL" && $search_textfield !="") {
          // echo "전체 키워드있음.";
          if($echo_text_cont == ""){
            // echo "  키워드 1개";
            $stmt = $this->conn->prepare("$str  $add_option and dc.cust_id =? order by dc.discount_rate $row_num,fav.prod_cd $row_num $limit");
            $stmt->bind_param("ss",$search_textfield,$cust);
          }else {
            // echo "  키워드 2개";
            $stmt = $this->conn->prepare("$str and prod_name like concat('%',?,'%')
            and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
            or fact_name like concat('%',?,'%')) and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
            $stmt->bind_param("sssss",$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$cust);
          }
        }else if ($class != "ALL" && $search_textfield !="") {
          //echo "분류 키워드있음";
          if ($class_cd =="ALL") {
            //echo "  1차전체";
            if($echo_text_cont == ""){
            //  echo "  키워드 1개";
              $stmt = $this->conn->prepare("$str
               and dc.PROD_CD like concat('%',?,'%')
               and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
               $stmt->bind_param("sss",$class,$cust,$search_textfield);
            }else {
            //  echo " 키워드 2개";
              $stmt = $this->conn->prepare("$str
               and dc.PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
               and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
               or fact_name like concat('%',?,'%'))
              and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
              $stmt->bind_param("ssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$cust);
            }
          }else {
            //echo "  1차분류";
            if ($class_cd_detail =="ALL") {
              //echo " 2차전체";
              if($echo_text_cont == ""){
//echo " 키워드 1개";
                $stmt = $this->conn->prepare("$str
                 and dc.PROD_CD like concat('%',?,'%')
                 and class_cd = ? and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
                $stmt->bind_param("ssss",$class,$search_textfield,$class_cd,$cust);
              }else {
                //echo " 키워드 2개";
                $stmt = $this->conn->prepare("$str
                 and dc.PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                 and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                 or fact_name like concat('%',?,'%')) and class_cd = ? and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
                $stmt->bind_param("sssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$cust);
              }
            }else {
            //  echo "  2차분류";
              if($echo_text_cont == ""){
                //echo "  키워드 1개";
                //echo "$class,$option,$search_textfield,$class_cd,$class_cd_detail,$cust";
                $stmt = $this->conn->prepare("$str
                 and dc.PROD_CD like concat('%',?,'%')
                   and class_cd = ? and class_detail_cd = ? and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
                  $stmt->bind_param("sssss",$class,$search_textfield,$class_cd,$class_cd_detail,$cust);
              }else {
                //echo "  키워드 2개";
                $stmt = $this->conn->prepare("$str
                 and dc.PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                 and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                 or fact_name like concat('%',?,'%')) and class_cd = ? and class_detail_cd = ? and dc.cust_id =? $add_option order by dc.discount_rate $row_num $limit");
                $stmt->bind_param("ssssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail,$cust);
              }

            }
          }

        }
        // $stmt->bind_param("sssss",$class,$class_cd,$class_cd_detail,$option,$search_textfield);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        }else {
              return SELECT_FAILED;
        }
    }

    public function selectMS_prod_cd_seller($class,$class_cd,$class_cd_detail,$option,$search_textfield,$sel,$s_point,$list)
    {
      $str = "SELECT dc.prod_cd,class_cd,class_detail_cd,
      prod_name,prod_cont,prod_wgt,sale_unit,fact_name,taxfree_yn,stn_cond_cd,
      prc.order_deadline_tm,origin_name,reg_date,update_date,dc.seller_id,dc.seller_prod_cd,sel.seller_name,prc.seller_prod_price,prc.seller_prod_name,prc.point_order_yn FROM TB_PROD pt
      join TB_SELLER_PROD_CD dc on pt.prod_cd = dc.prod_cd join TB_SELLER sel on dc.seller_id = sel.seller_id
      join TB_SELLER_PROD_PRICE  prc on  dc.seller_prod_cd=prc.seller_prod_cd and dc.seller_id=prc.seller_id";
      $plus_option = "prc.seller_id = '$sel' and prc.seller_prod_name like concat('%','$search_textfield','%') or ";
      if ($option == "dc.prod_cd" ||$option == "prc.seller_prod_cd"  ) {
        // code...
      }else{
        $option = "$plus_option"."$option";
      }
      if (isset($s_point) &&isset($list)  ) {
        $limit  = "limit $s_point,$list";
      }else {
        $limit  = "";
      }

      // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield,$sel";
        // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield";
        $echo_text = explode(" ",$search_textfield);
        $echo_text_name = $echo_text[0];
        $echo_text_cont = $echo_text[1];
        // echo "$echo_text_name / $echo_text_cont";
        if ($class == "ALL"&& $search_textfield =="") {
          // echo "전체";
          $stmt = $this->conn->prepare("$str
	         where dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
          $stmt->bind_param("s",$sel);
        }else if ($class != "ALL" && $search_textfield =="") {
          //echo "분류 키워드없음";
          if ($class_cd == "ALL") {
            //echo "  1차전체";
            $stmt = $this->conn->prepare("$str
  	        where dc.PROD_CD like concat('%',?,'%') and dc.seller_id =? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
            $stmt->bind_param("ss",$class,$sel);
          }else {
            //echo "  1차분류";
            if ($class_cd_detail =="ALL") {
              //echo "  2차전체";
              $stmt = $this->conn->prepare("$str
    	        where dc.PROD_CD like concat('%',?,'%') and class_cd = ? and dc.seller_id =? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
              $stmt->bind_param("sss",$class,$class_cd,$sel);
            }else {
              //echo "  2차분류";
              $stmt = $this->conn->prepare("$str
    	        where dc.PROD_CD like concat('%',?,'%') and class_cd = ? and class_detail_cd = ? and dc.seller_id =? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
              $stmt->bind_param("ssss",$class,$class_cd,$class_cd_detail,$sel);
            }
           }
        }else if ($class == "ALL" && $search_textfield !="") {
          //echo "전체 키워드있음.";
          if($echo_text_cont == ""){
            //echo "  키워드 1개";
            $stmt = $this->conn->prepare("$str
  	         where $option like concat('%',?,'%') and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
            $stmt->bind_param("ss",$search_textfield,$sel);
          }else {
            //echo "  키워드 2개";
            $stmt = $this->conn->prepare("$str
            where prod_name like concat('%',?,'%')
            and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
            or fact_name like concat('%',?,'%'))  and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
            $stmt->bind_param("sssss",$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$sel);
          }
        }else if ($class != "ALL" && $search_textfield !="") {
          //echo "분류 키워드있음";
          if ($class_cd =="ALL") {
            //echo "  1차전체";
            if($echo_text_cont == ""){
            //  echo "  키워드 1개";
              $stmt = $this->conn->prepare("$str
    	        where dc.PROD_CD like concat('%',?,'%') and $option like concat('%',?,'%') and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
              $stmt->bind_param("sss",$class,$search_textfield,$sel);
            }else {
            //  echo " 키워드 2개";
              $stmt = $this->conn->prepare("$str
              where  dc.PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
              and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
              or fact_name like concat('%',?,'%'))  and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
              $stmt->bind_param("ssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$sel);
            }
          }else {
            // echo "  1차분류";
            if ($class_cd_detail =="ALL") {
              // echo " 2차전체";
              if($echo_text_cont == ""){
                // echo " 키워드 1개";
                $stmt = $this->conn->prepare("$str
      	        where dc.PROD_CD like concat('%',?,'%') and $option like concat('%',?,'%') and class_cd = ? and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
                $stmt->bind_param("ssss",$class,$search_textfield,$class_cd,$sel);
              }else {
                // echo " 키워드 2개";
                $stmt = $this->conn->prepare("$str
                where  dc.PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                or fact_name like concat('%',?,'%')) and class_cd = ?  and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
                $stmt->bind_param("sssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$sel);
              }
            }else {
            //  echo "  2차분류";
              if($echo_text_cont == ""){
                // echo "  키워드 1개";
                //echo "$class,$option,$search_textfield,$class_cd,$class_cd_detail,$cust";
                $stmt = $this->conn->prepare("$str
      	        where dc.PROD_CD like concat('%',?,'%') and $option like concat('%',?,'%') and class_cd = ? and class_detail_cd = ? and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
                $stmt->bind_param("sssss",$class,$search_textfield,$class_cd,$class_cd_detail,$sel);
              }else {
                // echo "  키워드 2개";
                $stmt = $this->conn->prepare("$str
                where  dc.PROD_CD like concat('%',?,'%') and prod_name like concat('%',?,'%')
                and (prod_cont like concat('%',?,'%') or prod_wgt like concat('%',?,'%')
                or fact_name like concat('%',?,'%')) and class_cd = ?  and class_detail_cd = ? and  dc.seller_id=? and dc.seller_prod_cd=prc.seller_prod_cd $limit");
                $stmt->bind_param("ssssssss",$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail,$sel);
              }

            }
          }

        }
        // $stmt->bind_param("sssss",$class,$class_cd,$class_cd_detail,$option,$search_textfield);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        }else {
              return SELECT_FAILED;
        }
    }

    public function selectMSuseritem($cust_id,$seller,$class,$class_cd,$class_cd_detail,$option,$search_textfield,$where_active)
    {
        if ($where_active == "Y") {
           $where_active_yn = "and (pt.PROD_ACTIV_YN is null or pt.PROD_ACTIV_YN != '1')";
        }else {
           $where_active_yn = "";
        }
      // echo "$cust_id,셀러 : $seller,$class,$class_cd,$class_cd_detail,$option,$search_textfield";
      $echo_text = explode(" ",$search_textfield);
      $echo_text_name = $echo_text[0];
      $echo_text_cont = $echo_text[1];

      if($seller == "ALL"){
        $sel_id_lsit = "";
      }else {
        $sel_id_lsit = "sel_cd.seller_id = ? and";
      }
      // echo "$cust_id,$seller,$class,$class_cd,$class_cd_detail,$option,$search_textfield \n";
      if ($class == "ALL"&& $search_textfield =="") {
        // echo "전체 / 키워드X";
         $where =  "";
      }else if ($class != "ALL" && $search_textfield =="") {
        //echo "분류 / 키워드X";
        if ($class_cd == "ALL") {
          // echo "/ 1차 전체";
          $where =  "and pt.class_cd like concat('%',?,'%')";
        }else {
          if ($class_cd_detail =="ALL") {
            // echo "/ 2차 전체";
            $where =  "and pt.class_cd like concat('%',?,'%') and pt.class_cd = ?";
          }else {
             //echo "/ 2차 분류";
            $where =  "and pt.class_cd like concat('%',?,'%') and pt.class_cd =? and pt.class_detail_cd =?";
          }
        }
      }else if ($class == "ALL" && $search_textfield !="") {
         //echo "전체 / 키워드O";
        if($echo_text_cont == ""){
           //echo "/ 1개";
          $where =  "and $option like concat('%',?,'%')";
        }else {
           //echo "/ 2개";
           $where =  "and pt.prod_name like concat('%',?,'%')
                and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
                or pt.fact_name like concat('%',?,'%'))";
          // $stmt->bind_param("ssssss",$cust_id,$seller,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
        }
      }else if ($class != "ALL" && $search_textfield !="") {
        // echo "분류 / 키워드O";
        if ($class_cd == "ALL") {
          // echo "/ 1차 전체";
          if($echo_text_cont == ""){
          //   echo "/ 1개";
            $where =  "and pt.class_cd like concat('%',?,'%') and $option like concat('%',?,'%')";
          }else {
          //   echo "/ 2개";
            $where =  "and pt.class_cd like concat('%',?,'%') and pt.prod_name like concat('%',?,'%')
                 and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
                 or pt.fact_name like concat('%',?,'%'))";
          }
        }else {
          if ($class_cd_detail =="ALL") {
          //   echo "/ 2차 전체";
            if($echo_text_cont == ""){
            //   echo "1개";
              $where =  "and pt.class_cd like concat('%',?,'%') and $option like concat('%',?,'%') and pt.class_cd =?";
            }else {
              // echo "/ 2개";
              $where =  "and pt.class_cd like concat('%',?,'%') and pt.prod_name like concat('%',?,'%')
                   and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
                   or pt.fact_name like concat('%',?,'%')) and pt.class_cd =?";
            }
          }else {
            if($echo_text_cont == ""){
            //   echo "1개";
              $where =  "and pt.class_cd like concat('%',?,'%') and $option like concat('%',?,'%') and pt.class_cd =? and pt.class_detail_cd =?";
            }else {
              // echo "/ 2개";
               $where =  "and pt.class_cd like concat('%',?,'%') and pt.prod_name like concat('%',?,'%')
                    and (pt.prod_cont like concat('%',?,'%') or pt.prod_wgt like concat('%',?,'%')
                    or pt.fact_name like concat('%',?,'%')) and pt.class_cd =? and pt.class_detail_cd =?";
            }
          }
        }
      }

        $stmt = $this->conn->prepare("SELECT sel_cd.PROD_CD, sel_cd.SELLER_ID,sel.seller_name, sel_cd.SELLER_PROD_CD,
        round(sel_pr.SELLER_PROD_PRICE/0.95,-1),sel_pr.SELLER_PROD_NAME,pt.prod_cont,pt.prod_wgt,pt.fact_name,pt.prod_name FROM TB_SELLER_PROD_CD sel_cd
        join TB_SELLER_PROD_PRICE sel_pr on concat(sel_cd.seller_id,'_',sel_cd.SELLER_PROD_CD) =
        concat(sel_pr.seller_id,'_',sel_pr.SELLER_PROD_CD) join TB_SELLER sel on sel_cd.SELLER_ID =
        sel.SELLER_ID left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) dc
        on sel_cd.prod_cd=dc.prod_cd and sel_cd.seller_id=dc.seller_id
        join TB_PROD pt on sel_cd.prod_cd = pt.prod_cd
        WHERE $sel_id_lsit dc.prod_cd is null and sel_cd.SELLER_PROD_CD=sel_pr.SELLER_PROD_CD and sel_cd.SELLER_ID in (SELECT seller_id from TB_SELLER_BY_CUST where cust_id = ?)
        $where_active_yn $where and (pt.prod_cd != 'E0000000' and pt.prod_cd != 'E0000001' and pt.prod_cd != 'E0000002' and pt.prod_cd != 'E1000000')");
        	//특가상품 계란 / 김치
        //concat(sel_cd.prod_cd,'_',sel_cd.seller_id) = concat(dc.prod_cd,'_',dc.seller_id)
        // echo "1번 : $sel_id_lsit<br/>";
        // echo "2번 : $where<br/>";
if ($class == "ALL"&& $search_textfield =="") {
    if($seller == "ALL"){
      $stmt->bind_param("ss",$cust_id,$cust_id);
    }else {
      $stmt->bind_param("sss",$cust_id,$seller,$cust_id);
    }
}else if ($class != "ALL" && $search_textfield =="") {
  if ($class_cd == "ALL") {
    if($seller == "ALL"){
      $stmt->bind_param("sss",$cust_id,$cust_id,$class);
    }else {
      $stmt->bind_param("ssss",$cust_id,$seller,$cust_id,$class);
    }
  }else {
    if ($class_cd_detail =="ALL") {
      if($seller == "ALL"){
        $stmt->bind_param("ssss",$cust_id,$cust_id,$class,$class_cd);
      }else {
        $stmt->bind_param("sssss",$cust_id,$seller,$cust_id,$class,$class_cd);
      }
    }else {
      if($seller == "ALL"){
        $stmt->bind_param("sssss",$cust_id,$cust_id,$class,$class_cd,$class_cd_detail);
      }else {
        $stmt->bind_param("ssssss",$cust_id,$seller,$cust_id,$class,$class_cd,$class_cd_detail);
      }
    }
  }
}else if ($class == "ALL" && $search_textfield !="") {
  if($echo_text_cont == ""){
    if($seller == "ALL"){
      $stmt->bind_param("sss",$cust_id,$cust_id,$search_textfield);
    }else {
      $stmt->bind_param("ssss",$cust_id,$seller,$cust_id,$search_textfield);
    }
  }else {
    if($seller == "ALL"){
      $stmt->bind_param("ssssss",$cust_id,$cust_id,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
    }else {
      $stmt->bind_param("sssssss",$cust_id,$seller,$cust_id,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
    }
  }
}else if ($class != "ALL" && $search_textfield !="") {
  if ($class_cd == "ALL") {
    if($echo_text_cont == ""){
      if($seller == "ALL"){
        $stmt->bind_param("ssss",$cust_id,$cust_id,$class,$search_textfield);
      }else {
        $stmt->bind_param("sssss",$cust_id,$seller,$cust_id,$class,$search_textfield);
      }
    }else {
      if($seller == "ALL"){
        $stmt->bind_param("sssssss",$cust_id,$cust_id,$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
      }else {
        $stmt->bind_param("ssssssss",$cust_id,$seller,$cust_id,$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont);
      }
    }
  }else {
    if ($class_cd_detail =="ALL") {
      if($echo_text_cont == ""){
        if($seller == "ALL"){
          $stmt->bind_param("sssss",$cust_id,$cust_id,$class,$search_textfield,$class_cd);
        }else {
          $stmt->bind_param("ssssss",$cust_id,$seller,$cust_id,$class,$search_textfield,$class_cd);
        }
      }else {
        if($seller == "ALL"){
          $stmt->bind_param("ssssssss",$cust_id,$cust_id,$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd);
        }else {
          $stmt->bind_param("sssssssss",$cust_id,$seller,$cust_id,$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd);
        }
      }
    }else {
      if($echo_text_cont == ""){
        if($seller == "ALL"){
          $stmt->bind_param("ssssss",$cust_id,$cust_id,$class,$search_textfield,$class_cd,$class_cd_detail);
        }else {
          $stmt->bind_param("sssssss",$cust_id,$seller,$cust_id,$class,$search_textfield,$class_cd,$class_cd_detail);
        }
      }else {
        if($seller == "ALL"){
          $stmt->bind_param("sssssssss",$cust_id,$cust_id,$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail);
        }else {
          $stmt->bind_param("ssssssssss",$cust_id,$seller,$cust_id,$class,$echo_text_name,$echo_text_cont,$echo_text_cont,$echo_text_cont,$class_cd,$class_cd_detail);
        }
      }
    }
  }
}

        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function real_selectMSuseritem($cust_id,$seller,$class_cd1,$s_point,$list)
    {
      if ($class_cd1 == "ALL") {
        $stmt = $this->conn->prepare("SELECT sel_cd.PROD_CD, sel_cd.SELLER_ID,sel.seller_name, sel_cd.SELLER_PROD_CD,
sel_pr.SELLER_PROD_PRICE,sel_pr.SELLER_PROD_NAME FROM TB_SELLER_PROD_CD sel_cd
join TB_SELLER_PROD_PRICE sel_pr on concat(sel_cd.seller_id,'_',sel_cd.SELLER_PROD_CD) =
concat(sel_pr.seller_id,'_',sel_pr.SELLER_PROD_CD) join TB_SELLER sel on sel_cd.SELLER_ID =
sel.SELLER_ID left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) dc
on sel_cd.prod_cd=dc.prod_cd and sel_cd.seller_id=dc.seller_id
join TB_PROD pt on sel_cd.prod_cd = pt.prod_cd
WHERE sel_cd.seller_id = ? and dc.prod_cd is null order by sel_cd.prod_cd desc LIMIT ?,?");
        $stmt->bind_param("ssii",$cust_id,$seller,$s_point,$list);
      }else {
        $stmt = $this->conn->prepare("SELECT sel_cd.PROD_CD, sel_cd.SELLER_ID,sel.seller_name, sel_cd.SELLER_PROD_CD,
sel_pr.SELLER_PROD_PRICE,sel_pr.SELLER_PROD_NAME FROM TB_SELLER_PROD_CD sel_cd
join TB_SELLER_PROD_PRICE sel_pr on concat(sel_cd.seller_id,'_',sel_cd.SELLER_PROD_CD) =
concat(sel_pr.seller_id,'_',sel_pr.SELLER_PROD_CD) join TB_SELLER sel on sel_cd.SELLER_ID =
sel.SELLER_ID left join (SELECT * from TB_PROD_DISCOUNT where cust_id = ?) dc
on sel_cd.prod_cd=dc.prod_cd and sel_cd.seller_id=dc.seller_id
join TB_PROD pt on sel_cd.prod_cd = pt.prod_cd
WHERE sel_cd.seller_id = ? and dc.prod_cd is null and sel_cd.prod_cd like concat('%',?,'%')");
        $stmt->bind_param("sss",$cust_id,$seller,$class_cd1);
      }
      //concat(sel_cd.prod_cd,'_',sel_cd.seller_id) = concat(dc.prod_cd,'_',dc.seller_id)
      //concat(sel_cd.prod_cd,'_',sel_cd.seller_id) = concat(dc.prod_cd,'_',dc.seller_id)
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function selectMSsellerName($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT sel_cust.seller_id, sel.seller_name from TB_SELLER sel join TB_SELLER_BY_CUST sel_cust on sel.seller_id = sel_cust.seller_id where sel.activ_yn = 1 and cust_id = ? group by sel_cust.seller_id");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }
    public function admin_sellerMS_insert($prod_cd,$cust_id,$seller_id)
    {
        $serser = $_SERVER['PHP_SELF'];
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD_DISCOUNT(PROD_CD,CUST_ID,SELLER_ID,test) VALUES (?,?,?,'1번 $serser')");
        $stmt->bind_param("sss",$prod_cd,$cust_id,$seller_id);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function admin_sellerMS_insertSdg($prod_cd,$cust_id,$seller_id,$dis_rate)
    {
        $serser = $_SERVER['PHP_SELF'];
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD_DISCOUNT(PROD_CD,CUST_ID,SELLER_ID,DISCOUNT_RATE,test) VALUES (?,?,?,?,'2번 $serser')");
        $stmt->bind_param("sssd",$prod_cd,$cust_id,$seller_id,$dis_rate);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function admin_metching_insert($cust_id,$seller_id,$margin,$cust_code,$staf)
    {
        if (empty($staf) || !isset($staf) || $staf=="") {
          $stmt = $this->conn->prepare("INSERT INTO TB_SELLER_BY_CUST(CUST_ID,SELLER_ID,MARGIN_RATE,CUST_cd) VALUES (?,?,?,?)");
          $stmt->bind_param("ssis",$cust_id,$seller_id,$margin,$cust_code);
        }else {
          $stmt = $this->conn->prepare("INSERT INTO TB_SELLER_BY_CUST(CUST_ID,SELLER_ID,MARGIN_RATE,CUST_cd,STAFF_NO) VALUES (?,?,?,?,?)");
          $stmt->bind_param("ssiss",$cust_id,$seller_id,$margin,$cust_code,$staf);
        }
        if ($stmt->execute()) {
            $this->insertByLogHis('등록',$cust_id,$seller_id,$margin,55000);
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
    }

    public function admin_metching_delete($cust_id,$seller_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM TB_SELLER_BY_CUST WHERE cust_id = ? and seller_id = ?");
        $stmt->bind_param("ss",$cust_id,$seller_id);
        if ($stmt->execute()) {
            $this->insertByLogHis('삭제',$cust_id,$seller_id);
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }
    public function admin_metching_prod_delete($cust_id,$seller_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM TB_PROD_DISCOUNT WHERE cust_id = ? and seller_id = ?");
        $stmt->bind_param("ss",$cust_id,$seller_id);
        if ($stmt->execute()) {
            return DELETE_COMPLETED;
        } else {
            return DELETE_FAILED;
        }
    }

    public function cartMSgroupName($cust_id)
    {
        $stmt = $this->conn->prepare("SELECT sel.seller_name,cart.seller_id,sel.weekday,sel.weekend,sel.holiday FROM TB_CART cart join TB_SELLER sel on cart.seller_id = sel.seller_id where cust_id = ? group by cart.seller_id");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }

    }

    public function insert_sel_memo($order_no,$seller_id,$memo)
    {
        $stmt = $this->conn->prepare("INSERT INTO TB_CUST_MEMO(order_no,seller_id,memo) VALUES (?,?,?)");
        $stmt->bind_param("iss",$order_no,$seller_id,$memo);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }

    public function update_sel_memo($req)
    {
        $order_no=$req["order_no"];//주문번호
        $seller_id=$req["seller_id"];//유통사아이디
        $memo=$req["memo"];//메모사항
        $stmt = $this->conn->prepare("UPDATE TB_CUST_MEMO SET memo = '$memo' WHERE order_no = '$order_no' AND seller_id = '$seller_id'");
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }

      public function update_admin_percent($discount,$prod_cd,$cust_id,$seller_id)
      {
          $stmt = $this->conn->prepare("UPDATE TB_PROD_DISCOUNT SET DISCOUNT_RATE=? WHERE PROD_CD=? and CUST_ID=? and SELLER_ID=?");
          $stmt->bind_param("dsss",$discount,$prod_cd,$cust_id,$seller_id);
          if ($stmt->execute()) {
              return UPDATE_COMPLETED;
          } else {
              return UPDATE_FAILED;
          }
        }

        public function checkCustID($cust_id)
        {
            $stmt = $this->conn->prepare("SELECT CUST_ID,BUSINESS_NAME FROM TB_CUST WHERE cust_id=?");
            $stmt->bind_param("s",$cust_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return SELECT_SUCCESS;
            } else {
                return SELECT_FAILED;
            }
        }
        public function checkCustInfo($cust_id)
        {
            $stmt = $this->conn->prepare("SELECT CUST_ID,BUSINESS_NAME FROM TB_CUST WHERE cust_id=?");
            $stmt->bind_param("s",$cust_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
        }

        // 2021-09-03 쿠폰 일괄 발급 업장 체크
        public function checkCustInfoAll($cust_id)
        {
          $query = "SELECT CUST_ID, BUSINESS_NAME, TEL_NO
                    FROM TB_CUST
                    WHERE cust_id = '$cust_id'";

          $stmt = $this->conn->prepare($query);

          $stmt->execute();
          $stmt->store_result();

          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
        }



    // //make 페이지 종류마다의 정보 (견적완료페이지)
    // public function imagesSelect(){
    //   $stmt = $this ->conn->prepare("SELECT id,img,title,width,height,filesize FROM images WHERE 1");
    //   // $stmt->bind_param("iis",$er_no_hidden,$er_ans_no,$er_prod_class_cd);
    //   $stmt->execute();
    //   $stmt->store_result();
    //   if($stmt->num_rows > 0){
    //     return $stmt;
    //   }else{
    //     return SELECT_FAILED;
    //   }
    // }
    //
    // public function imagesSelectWhere($id){
    //   $stmt = $this ->conn->prepare("SELECT img FROM images WHERE id=?");
    //   $stmt->bind_param("s",$id);
    //   $stmt->execute();
    //   $stmt->store_result();
    //   if($stmt->num_rows > 0){
    //     return $stmt;
    //   }else{
    //     return SELECT_FAILED;
    //   }
    // }

    public function selectDB()
    {
        $stmt = $this->conn->prepare("SELECT prod_cd FROM TB_PROD");
        // $stmt->bind_param("s",$prod_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
    }

    public function updateMSimg($img,$prod_cd)
    {
        $stmt = $this->conn->prepare("UPDATE TB_PROD SET img = ? WHERE PROD_CD= ?");
        $stmt->bind_param("ss",$img,$prod_cd);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }

      public function selectCheckMSCD($prod_name,$prod_cont,$prod_wgt,$fact_name)
      {
          $stmt = $this->conn->prepare("SELECT prod_cd FROM TB_PROD WHERE prod_name = ? and prod_cont = ? and prod_wgt = ? and fact_name = ?");
          $stmt->bind_param("ssss",$prod_name,$prod_cont,$prod_wgt,$fact_name);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return SELECT_COMPLETED;
          } else {
              return SELECT_FAILED;
          }
      }

      public function selectDBS($prod_cd,$seller_id)
      {
          $stmt = $this->conn->prepare("SELECT PROD_CD,SELLER_ID,SELLER_PROD_CD FROM TB_SELLER_PROD_CD where prod_cd = ? and seller_id = ? order by prod_cd asc");
          $stmt->bind_param("ss",$prod_cd,$seller_id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function selectDB_seller_prod()
      {
          $stmt = $this->conn->prepare("SELECT PROD_CD,SELLER_ID,SELLER_PROD_CD FROM TB_SELLER_PROD_CD order by prod_cd asc");
          // $stmt->bind_param("s",$prod_cd);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }


      public function insert_cust_payment($cust_id)
{
    $stmt = $this->conn->prepare("INSERT INTO TB_CUST_PAYMENT
      (CUST_ID,CUST_ACCN_BN_NAME,CUST_ACCN_NO,DEPOSIT_BLN,UPDATE_DATE,CUST_DEPOSIT_ACCN_NO)
SELECT ?,'국민은행','801701-04-253587',0,now(),DEPOSIT_ACCN_NO
FROM TB_DEPOSIT_ACCN WHERE CUST_ID is null  order by DEPOSIT_ACCN_NO LIMIT 1");
    $stmt->bind_param("s",$cust_id);
    if ($stmt->execute()) {
      $upAccn = $this->update_cust_depositAccn($cust_id);
        return INSERT_COMPLETED;
    } else {
        return INSERT_FAILED;
    }
  }
      public function update_cust_depositAccn($cust_id)
{
    $stmt = $this->conn->prepare("UPDATE TB_DEPOSIT_ACCN,(SELECT DEPOSIT_ACCN_NO
    FROM TB_DEPOSIT_ACCN WHERE CUST_ID is null  order by DEPOSIT_ACCN_NO LIMIT 1) B
      SET TB_DEPOSIT_ACCN.CUST_ID = ?
      WHERE TB_DEPOSIT_ACCN.DEPOSIT_ACCN_NO = B.DEPOSIT_ACCN_NO");
    $stmt->bind_param("s",$cust_id);
    if ($stmt->execute()) {
        return UPDATE_COMPLETED;
    } else {
        return UPDATE_FAILED;
    }
  }
//       public function insert_cust_payment($cust_id)
// {
//     $stmt = $this->conn->prepare("INSERT INTO TB_CUST_PAYMENT(CUST_ID,CUST_ACCN_BN_NAME,CUST_ACCN_NO,DEPOSIT_BLN,UPDATE_DATE) VALUES (?,'국민은행','801701-04-253587',0,now())");
//     $stmt->bind_param("s",$cust_id);
//     if ($stmt->execute()) {
//         return INSERT_COMPLETED;
//     } else {
//         return INSERT_FAILED;
//     }
//   }

  public function update_cust_payment($credit,$cust_id)
{
$stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT SET CREDIT_LIMIT= ? WHERE cust_id = ?");
$stmt->bind_param("is",$credit,$cust_id);
if ($stmt->execute()) {
    return INSERT_COMPLETED;
} else {
    return INSERT_FAILED;
}
}
public function update_cust_calc($settlement,$cust_id)
{
$stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT SET SETTLEMENT_DT= ? WHERE cust_id = ?");
$stmt->bind_param("ss",$settlement,$cust_id);
if ($stmt->execute()) {
  return INSERT_COMPLETED;
} else {
  return INSERT_FAILED;
}
}
public function update_min_pay($pay,$seller_id,$cust_id)
{
$stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET MIN_ORDER_PR=? WHERE seller_id = ? and cust_id = ?");
$stmt->bind_param("iss",$pay,$seller_id,$cust_id);
if ($stmt->execute()) {
  return INSERT_COMPLETED;
} else {
  return INSERT_FAILED;
}
}

      public function admin_order_list($occd,$search_text,$search_textfield,$date1,$date2,$seller_id,$sel_type,$s_point,$list,$coupon_list_show,$sdg_list_show,$day){
        $select = "SELECT ord.ORDER_NO,item.ORDER_COND_CD,cond.ORDER_COND_NAME,ord.CUST_ID,
        cust.BUSINESS_NAME,item.seller_id,sel.seller_name,item.payshow,
        item.ARRIVE_DATE,ord.ORDER_DATE,ord.wtid,item.con_tm,item.co_pr
        FROM TB_ORDER ord join
        (SELECT ORDER_DEADLINE_TM,ORDER_ITEM_NO,ORDER_NO,SELLER_ID,
          PROD_CD,PROD_ORDER_CNT,order_cond_cd,sum(round(order_pay*prod_order_cnt))as payshow,
          left(GROUP_CONCAT(DISTINCT ORDER_DEADLINE_TM order by ORDER_DEADLINE_TM desc),1) as con_tm,
          sum(coupon_price) as co_pr,ARRIVE_DATE
          FROM TB_ORDER_ITEM group by order_no,seller_id,order_cond_cd,ORDER_DEADLINE_TM) item
          on ord.ORDER_NO= item.ORDER_NO
          join TB_SELLER sel on item.seller_id=sel.seller_id
          join TB_CUST cust on ord.cust_id=cust.cust_id
          join (SELECT * from TB_CUST_PAYMENT_HIS group by order_no) pay
          on item.order_no = pay.order_no join TB_ORDER_COND_CD cond
          on item.order_cond_cd = cond.ORDER_COND_CD
          left join (SELECT * from TB_COUPON_HIS group by order_no,seller_id) his
          on ord.ORDER_NO=his.ORDER_NO and item.seller_id=his.SELLER_ID";
        // $group_by = "group by pay.order_no,item.order_no";
        if (isset($day)) {
          $admin = " and item.ORDER_DEADLINE_TM = '$day' ";
        }else {
          $admin = "";
        }

        if (isset($s_point) && isset($list)) {
          $limit = "limit $s_point,$list";
        }else {
          $limit = "";
        }

        if ($seller_id == "" || empty($seller_id) || !isset($seller_id)) {
          $seller = "";
        }else {
          if ($sel_type == "SELLER") {
          $select = "SELECT ord.ORDER_NO,item.ORDER_COND_CD,cond.ORDER_COND_NAME,
          ord.CUST_ID,cust.BUSINESS_NAME,item.seller_id,sel.seller_name,
          item.payshow,ord.REG_DATE,ord.ORDER_DATE,ord.wtid,item.con_tm
          FROM TB_ORDER ord join (SELECT ORDER_DEADLINE_TM,ORDER_ITEM_NO,ORDER_NO,
            SELLER_ID,PROD_CD,PROD_ORDER_CNT,order_cond_cd,sum(if(taxfree_yn=1,order_sel_costpr,
            round(order_sel_costpr*1.1))*prod_order_cnt)as payshow,
            left(GROUP_CONCAT(DISTINCT ORDER_DEADLINE_TM order by ORDER_DEADLINE_TM desc),1) as  con_tm,
            sum(coupon_price) as co_pr
            FROM (SELECT i.order_cond_cd,i.seller_id,i.order_no,i.order_item_no,i.prod_cd,
            i.prod_order_cnt,i.order_sel_costpr,i.order_deadline_tm,p.taxfree_yn,i.coupon_price
            from TB_ORDER_ITEM i left join TB_PROD p on i.PROD_CD = p.PROD_CD) ip
            group by order_no,seller_id,order_cond_cd) item on ord.ORDER_NO= item.ORDER_NO
            join TB_SELLER sel on item.seller_id=sel.seller_id
            join TB_CUST cust on ord.cust_id=cust.cust_id
            join (SELECT * from TB_CUST_PAYMENT_HIS group by order_no) pay
            on item.order_no = pay.order_no join TB_ORDER_COND_CD cond
            on item.order_cond_cd = cond.ORDER_COND_CD";
          $seller = "and item.seller_id = '$seller_id'";
         }elseif ($sel_type == "SALES") {
            $select = "SELECT ord.ORDER_NO,item.ORDER_COND_CD,cond.ORDER_COND_NAME,
            ord.CUST_ID,cust.BUSINESS_NAME,item.seller_id,sel.seller_name,
            item.payshow,ord.REG_DATE,ord.ORDER_DATE,ord.wtid,item.con_tm,
            item.co_pr FROM TB_ORDER ord join
            (SELECT ORDER_DEADLINE_TM,ORDER_ITEM_NO,ORDER_NO,SELLER_ID,PROD_CD,
              PROD_ORDER_CNT,order_cond_cd,sum(round(order_pay*prod_order_cnt))as payshow,
              left(GROUP_CONCAT(DISTINCT ORDER_DEADLINE_TM order by ORDER_DEADLINE_TM desc),1) as  con_tm,
              sum(coupon_price) as co_pr
              FROM TB_ORDER_ITEM group by order_no,seller_id,ORDER_DEADLINE_TM,order_cond_cd) item
              on ord.ORDER_NO= item.ORDER_NO join TB_SELLER sel on item.seller_id=sel.seller_id
              join TB_CUST cust on ord.cust_id=cust.cust_id join (SELECT * from TB_CUST_PAYMENT_HIS
              group by order_no) pay on item.order_no = pay.order_no join TB_ORDER_COND_CD cond
              on item.order_cond_cd = cond.ORDER_COND_CD
              left join (SELECT * from TB_COUPON_HIS group by order_no,seller_id) his
              on ord.ORDER_NO=his.ORDER_NO and item.seller_id=his.SELLER_ID
              join TB_ADMIN admin on cust.admin_id = admin.admin_id";
            $seller = "and admin.admin_id = '$seller_id'";
          }else {
            $select = "SELECT ord.ORDER_NO,item.ORDER_COND_CD,cond.ORDER_COND_NAME,ord.CUST_ID,
            cust.BUSINESS_NAME,item.seller_id,sel.seller_name,item.payshow,
            ord.REG_DATE,ord.ORDER_DATE,ord.wtid,item.con_tm,item.co_pr
            FROM TB_ORDER ord join (SELECT ORDER_DEADLINE_TM,ORDER_ITEM_NO,ORDER_NO,
              SELLER_ID,PROD_CD,PROD_ORDER_CNT,order_cond_cd,sum(round(order_pay*prod_order_cnt))as payshow,
              left(GROUP_CONCAT(DISTINCT ORDER_DEADLINE_TM order by ORDER_DEADLINE_TM desc),1) as  con_tm,
              sum(coupon_price) as co_pr
              FROM TB_ORDER_ITEM group by order_no,seller_id,order_cond_cd,ORDER_DEADLINE_TM) item
              on ord.ORDER_NO= item.ORDER_NO join TB_SELLER sel on item.seller_id=sel.seller_id
              join TB_CUST cust on ord.cust_id=cust.cust_id join
              (SELECT * from TB_CUST_PAYMENT_HIS group by order_no) pay
              on item.order_no = pay.order_no join TB_ORDER_COND_CD cond
              on item.order_cond_cd = cond.ORDER_COND_CD
              left join (SELECT * from TB_COUPON_HIS group by order_no,seller_id) his
              on ord.ORDER_NO=his.ORDER_NO and item.seller_id=his.SELLER_ID";
            $seller = "and item.seller_id = '$seller_id'";
          }
          // echo " $sel_type ?? $seller ??????????????";

        }
        $order_by = "ORDER BY ord.REG_DATE desc,ord.ORDER_NO desc";
        $str = "";
        // $group_seller = "and item.seller_id = ''";
        if ($date1 == "" || $date2 == "" ) {
          $date_str = "";
        }else {
          $date_str = "and date(ord.reg_date) BETWEEN '$date1' and '$date2'";
        }
        // where his.coupon_discount_price is not null
        if ($occd == "ALL") {
          $where = "where 1 $date_str";
        }else{
          $where = "where item.ORDER_COND_CD = '$occd' $date_str";
        }
        if ($coupon_list_show == "show") {
          if ($sel_type == "SELLER") {
          }else {
          $where = "$where"."and his.coupon_discount_price is not null";
          }
        }
        if ($sdg_list_show == "show") {
          $where = "$where"." and cust.DELIV_POSITION REGEXP ('$this->serviceList')";
        }else if($sdg_list_show == "normal"){
          $where = "$where"." and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null)";
          //and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null)
        }else if($sdg_list_show == "sungsoo"){
          $where = "$where"." and cust.DELIV_POSITION REGEXP ('$this->sungsooList')";
        }else if($sdg_list_show == "wang1"){
          $where = "$where"." and cust.DELIV_POSITION REGEXP ('$this->wang1List')";
        }else if($sdg_list_show == "songjung"){
          $where = "$where"." and cust.DELIV_POSITION REGEXP ('$this->songjung')";
        }
        if ($search_text == "order" && $search_textfield != "") {
          $and = "and ord.order_no = $search_textfield";
          $str = "1";
        }else if($search_text == "cust" && $search_textfield != ""){
          $and = "and (ord.CUST_ID like concat('%','$search_textfield','%') or cust.BUSINESS_NAME like concat('%','$search_textfield','%'))";
          $str = "2";
        }else if($search_text == "seller" && $search_textfield != "") {
          $and = "and (item.seller_id like concat('%','$search_textfield','%') or sel.seller_name like concat('%','$search_textfield','%'))";
          $str = "2";
        }
        // echo "$select $where $and $seller $admin $order_by $limit";
        $stmt = $this->conn->prepare("$select $where $and $seller $admin $order_by $limit");
        if ($str != "") {
          if ($str == "1") {
            if ($seller == "") {
              // $stmt->bind_param("s",$search_textfield);
            }else {
              // $stmt->bind_param("ss",$search_textfield,$seller_id);
            }
          }else {
            if ($seller == "") {
              // $stmt->bind_param("ss",$search_textfield,$search_textfield);
            }else {
              // $stmt->bind_param("sss",$search_textfield,$search_textfield,$seller_id);
            }
          }
        }
        //
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }

      public function seller_orignal_order_total($order_no,$seller_id){

                                          $select = "SELECT ord.ORDER_NO,item.ORDER_COND_CD,cond.ORDER_COND_NAME,ord.CUST_ID,cust.BUSINESS_NAME,item.seller_id,sel.seller_name,item.payshow,ord.REG_DATE,ord.ORDER_DATE,ord.wtid,item.con_tm FROM TB_ORDER ord join (SELECT ORDER_ITEM_NO,ORDER_NO,SELLER_ID,PROD_CD,PROD_ORDER_CNT,order_cond_cd,sum(if(taxfree_yn=1,order_sel_costpr,round(order_sel_costpr*1.1))*prod_order_cnt)as payshow,if(left(GROUP_CONCAT(DISTINCT ORDER_DEADLINE_TM order by ORDER_DEADLINE_TM desc),1) != '1','N','Y') as  con_tm  FROM
(SELECT i.order_cond_cd,i.seller_id,i.order_no,i.order_item_no,i.prod_cd,i.prod_order_cnt,i.order_sel_costpr,i.order_deadline_tm,p.taxfree_yn from TB_ORDER_ITEM i left join TB_PROD p on i.PROD_CD = p.PROD_CD) ip group by order_no,seller_id,order_cond_cd) item on ord.ORDER_NO= item.ORDER_NO join TB_SELLER sel on item.seller_id=sel.seller_id join TB_CUST cust on ord.cust_id=cust.cust_id join (SELECT * from TB_CUST_PAYMENT_HIS group by order_no) pay on item.order_no = pay.order_no join TB_ORDER_COND_CD cond on item.order_cond_cd = cond.ORDER_COND_CD";

        $where = "where ord.order_no = $order_no and item.seller_id = '$seller_id'";
        $stmt = $this->conn->prepare("$select $where");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }



      public function select_deposit_order_list($id,$order_no)
      {
          $stmt = $this->conn->prepare("SELECT cust.business_name,cust.owner_name,cust.addr_cd,cust.addr_cont,cust.tel_no,cust.activ_yn,
          cust.ad_aggr_yn,cust.reg_date,ord.order_no,ord.order_date,ord.order_cond_cd,ord.cust_id,
          ord.reg_date,ord.update_date,ord.memo,ord.wtid FROM TB_CUST cust
          join(SELECT * from TB_ORDER where order_no= $order_no) ord on cust.CUST_ID = ord.cust_id
          where cust.cust_id = ?");
          $stmt->bind_param("s",$id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_select_cust($id,$vw)
      {
        if ($vw == "VW") {
          $stmt = $this->conn->prepare("SELECT business_name,owner_name,addr_cd,addr_cont,tel_no,activ_yn,ad_aggr_yn,reg_date,
            CUST_REFUND_ACCN_NO,CUST_REFUND_ACCN_NAME,CUST_REFUND_ACCN_BN_CD
            FROM TB_CUST cust join TB_CUST_PAYMENT pmt on cust.cust_id = pmt.cust_id where cust.cust_id = ?");
        }else {
          $stmt = $this->conn->prepare("SELECT business_name,owner_name,addr_cd,addr_cont,tel_no,activ_yn,ad_aggr_yn,reg_date
            FROM TB_CUST where cust_id = ?");
        }
          $stmt->bind_param("s",$id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_select_seller($id)
      {
          $stmt = $this->conn->prepare("SELECT seller_name,addr_cd,addr_cont,tel_no,activ_yn,code_own_yn,weekday,weekend,holiday FROM TB_SELLER WHERE seller_id = ?");
          $stmt->bind_param("s",$id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_deposit_list($cust_id,$payment_his_cd,$date1,$date2)
      {
        // echo "$cust_id,$payment_his_cd,$date1,$date2";
          if ($cust_id == "") {
            $cust = "";
          }else {
          $cust = "and cust.cust_id = '$cust_id'";
          }
          if ($payment_his_cd == "BD") {
            $order_by = "order by payment_date desc";
          }else {
            $order_by = "order by payment_date desc,his.no desc";
          }


          if ($date1 == "" || $date2 == "" ) {
            $date_str = "";
          }else {
            $date_str = "and date(his.PAYMENT_DATE) BETWEEN '$date1' and '$date2'";
          }

          if ($payment_his_cd == "ALL"  || $payment_his_cd == "" || !isset($payment_his_cd) || empty($payment_his_cd)){
            $stmt = $this->conn->prepare("SELECT his.order_no,his.cust_id,cust.business_name,his.payment_pr,his.deposit_bln,
              his.payment_date,cust.owner_name,cust.tel_no,
              his.memo,cd.PAYMENT_HIS_NAME,his.seller_id,his.PAYMENT_HIS_MEMO
              FROM TB_CUST_PAYMENT_HIS his join TB_CUST cust on his.cust_id = cust.cust_id
              join TB_PAYMENT_HIS_CD cd on his.payment_his_cd = cd.payment_his_cd
              where  his.payment_his_cd != 'CR' $cust $date_str $order_by");
          }else {
            $stmt = $this->conn->prepare("SELECT his.order_no,his.cust_id,cust.business_name,his.payment_pr,his.deposit_bln,
              his.payment_date,cust.owner_name,cust.tel_no,
              IF(his.order_no = hisbb.order_no,concat('[입금취소]<br/>',his.memo),his.memo),
              cd.PAYMENT_HIS_NAME,his.seller_id,his.PAYMENT_HIS_MEMO
              FROM TB_CUST_PAYMENT_HIS his join TB_CUST cust on his.cust_id = cust.cust_id
              join TB_PAYMENT_HIS_CD cd on his.payment_his_cd = cd.payment_his_cd
              left join (SELECT * from TB_CUST_PAYMENT_HIS where payment_his_cd = 'BB') hisbb on his.order_no = hisbb.order_no
              where  his.payment_his_cd != 'CR' $date_str and  his.payment_his_cd = '$payment_his_cd' $cust $order_by");
          }

          //(his.payment_his_cd = 'BD' or his.payment_his_cd = 'BW')
          // $stmt->bind_param("s",$id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function couponTaxFreeNY($order_no,$sel){
        $select = "SELECT if(prd.TAXFREE_YN=1,'면세','과세') as taxfree,sum(item.coupon_price)
        FROM TB_ORDER_ITEM item
        left join TB_PROD prd on item.PROD_CD = prd.PROD_CD
        WHERE item.ORDER_NO = $order_no and item.SELLER_ID = '$sel'
        group by taxfree,item.SELLER_ID";
        $stmt = $this->conn->prepare("$select");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }
      //21-02-26 소수점 증/감량 수정쿼리
      public function orderpay_list_select($date1,$date2,$sel_id,$occd,$yn,$cust_id,$s_point,$list,$admin_type,$admin_id,$sales_select,$sdg_get){
        if (isset($s_point) && isset($list)) {
          $limit = "limit $s_point,$list";
        }else {
          $limit = "";
        }
        // echo "$y,$mm,$sel_id,$occd,$yn,$cust_id";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   if ($dd == "00") {
        //     $date = "$y"."-"."$mm";
        //     $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        //   }else {
        //     $date = "$y"."-"."$mm"."-"."$dd";
        //     $date_format = "date_format(ord.ORDER_DATE,'%Y-%m-%d') = ?";
        //   }
        // }
        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
        if ($sel_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$sel_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }

        if ($occd == "ALL") {
          $order_cond_cd= "";
        }else {
          $order_cond_cd = "and item.order_cond_cd = $occd";
        }

        if ($yn == 2) {
          $tx_yn = "group by item.ORDER_ITEM_NO order by ord.order_date asc,item.ORDER_ITEM_NO DESC";
        }else {
          $tx_yn = "and prd.TAXFREE_YN = $yn group by item.ORDER_ITEM_NO order by ord.order_date asc,item.ORDER_ITEM_NO DESC ";
        }
        // echo "날짜 : $date";
        // echo "셀러 : $sel_id";
        // echo "상태 : $occd";
        // echo "면세 : $yn";

        if ($admin_type == "SALES") {
          $admin_type_join = " join (SELECT * from TB_ADMIN where admin_id = '$admin_id') admin
          on cust.admin_id = admin.admin_id";
        }else {
          $admin_type_join = "";
        }

        if ($sales_select == "ALL") {
          $sales_select_where = "";
        }elseif ($sales_select == "NONE") {
          $sales_select_where = " and cust.admin_name is null ";
        }else {
          $sales_select_where = " and cust.admin_id ='$sales_select' ";
        }
        //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
        }else if($sdg_get == "ALL"){
          $sdg_get_where = "";
        }else if($sdg_get == "sdg"){
          $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
          $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }

        $stmt = $this->conn->prepare("SELECT item.order_item_no 아이템번호,item.coupon_price 쿠폰할인액,ord.ORDER_DATE 주문날짜,ord.ORDER_NO 주문번호,
          item.SELLER_ID 유통아이디,sel.SELLER_NAME 유통사명,ord.CUST_ID 식당아이디,
          cust.BUSINESS_NAME 식당명,cust.OWNER_NAME 식당대표자명, item.PROD_CD MS상품코드,item.SELLER_PROD_CD 유통사상품코드,
          prd.PROD_NAME 상품명,prd.PROD_CONT 상품내용,prd.PROD_WGT 상품중량,prd.FACT_NAME 생산지,
          prd.TAXFREE_YN 면세코드,if(prd.TAXFREE_YN='1','면세','과세') 면세여부,prd.STN_COND_CD 보관상태,
          item.ORDER_DEADLINE_TM 배송기간, item.order_cond_cd 주문상태코드,
          ord_cond_cd.ORDER_COND_NAME 주문상태, item.order_pay 상품금액,
          item.PROD_ORDER_CNT 주문수량 ,(item.order_pay * item.prod_order_cnt) 상품금액X주문수량,
          if(prd.TAXFREE_YN='1',item.order_pay,item.order_costpr) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay-item.order_costpr) 부가세,
          if(prd.TAXFREE_YN='1',(item.order_pay * item.prod_order_cnt),(item.order_costpr * item.prod_order_cnt)) 총공급가,
          if(prd.TAXFREE_YN='1',0,((item.order_pay-item.order_costpr) * item.prod_order_cnt)) 총부가세,
          if(ord.wtid is null  or ord.wtid ='','deposit',if(INSTR(ord.wtid,'VBNK'),'vbank','card')) 카드결제여부,coupon_his.COUPON_NO 쿠폰번호,
          coupon_his.COUPON_DISCOUNT_PRICE 쿠폰금액,
          cust.DELIV_POSITION as 성수동,cust.admin_name as 매칭영업사원,ord.wtid as WTID,item.arrive_date as 입고예정일,
          soi.SELLER_ORDER_NAME as 매입처주문명,memo.memo
          FROM TB_ORDER_ITEM item
          left join TB_COUPON_HIS coupon_his on item.ORDER_NO = coupon_his.ORDER_NO and item.SELLER_ID = coupon_his.SELLER_ID
          right join TB_PROD prd on item.PROD_CD = prd.PROD_CD join TB_ORDER_COND_CD ord_cond_cd
          on item.order_cond_cd = ord_cond_cd.ORDER_COND_CD join TB_SELLER sel on item.SELLER_ID = sel.SELLER_ID
          join TB_ORDER ord on ord.order_no = item.order_no
          inner join (SELECT ct.*,adn.admin_name from TB_CUST ct left join TB_ADMIN adn on ct.admin_id = adn.admin_id) cust on ord.cust_id = cust.cust_id
          left join TB_SELLER_ORDER_INFO soi on ord.CUST_ID = soi.CUST_ID and item.SELLER_ID = soi.SELLER_ID
          left join (SELECT ORDER_NO,SELLER_ID,memo FROM TB_CUST_MEMO where memo is not null and memo != '') as memo
          on ord.ORDER_NO = memo.ORDER_NO and item.SELLER_ID = memo.SELLER_ID
          $admin_type_join
          WHERE $date_format $seller $cust $order_cond_cd $sales_select_where $sdg_get_where $tx_yn $limit");
          //if(prd.TAXFREE_YN='1',item.order_pay,item.order_costpr) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay-item.order_costpr) 부가세
          //if(prd.TAXFREE_YN='1',item.order_pay,item.order_pay/1.1) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay/11) 부가세
        // if ($mm == "00") {
        //   $stmt->bind_param("s",$y);
        // }else {
        //   $stmt->bind_param("ss",$y,$mm);
        // }
        // $stmt->bind_param("s",$date);
        $stmt->execute();
        $stmt->store_result();
        // echo "검색된 행 갯수 : $stmt->num_rows"."개";
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }
      //21-02-26 소수점 증/감량 수정쿼리
      public function orderpay_list_select_sellerveiw($date1,$date2,$sel_id,$occd,$yn,$cust_id,$s_point,$list){
        if (isset($s_point) && isset($list)) {
          $limit = "limit $s_point,$list";
        }else {
          $limit = "";
        }
        // echo "$y,$mm,$sel_id,$occd,$yn,$cust_id";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   if ($dd == "00") {
        //     $date = "$y"."-"."$mm";
        //     $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        //   }else {
        //     $date = "$y"."-"."$mm"."-"."$dd";
        //     $date_format = "date_format(ord.ORDER_DATE,'%Y-%m-%d') = ?";
        //   }
        // }
        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
        if ($sel_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$sel_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }

        if ($occd == "ALL") {
          $order_cond_cd= "";
        }else {
          $order_cond_cd = "and item.order_cond_cd = $occd";
        }

        if ($yn == 2) {
          $tx_yn = "order by ord.order_date asc,item.ORDER_ITEM_NO DESC";
        }else {
          $tx_yn = "and prd.TAXFREE_YN = $yn order by ord.order_date asc,item.ORDER_ITEM_NO DESC ";
        }
        // echo "날짜 : $date";
        // echo "셀러 : $sel_id";
        // echo "상태 : $occd";
        // echo "면세 : $yn";

        $stmt = $this->conn->prepare("SELECT ord.ORDER_DATE 주문날짜,ord.ORDER_NO 주문번호,
          item.SELLER_ID 유통아이디,sel.SELLER_NAME 유통사명,ord.CUST_ID 식당아이디,
          cust.BUSINESS_NAME 식당명,cust.OWNER_NAME 식당대표자명, item.PROD_CD MS상품코드,item.SELLER_PROD_CD 유통사상품코드,
          prd.PROD_NAME 상품명,prd.PROD_CONT 상품내용,prd.PROD_WGT 상품중량,prd.FACT_NAME 생산지,
          prd.TAXFREE_YN 면세코드,if(prd.TAXFREE_YN='1','면세','과세') 면세여부,prd.STN_COND_CD 보관상태,
          item.ORDER_DEADLINE_TM 배송기간, item.order_cond_cd 주문상태코드,
          ord_cond_cd.ORDER_COND_NAME 주문상태, if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) 상품금액,
          item.PROD_ORDER_CNT 주문수량 ,(if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) * item.prod_order_cnt) 상품금액X주문수량,
          item.order_sel_costpr 공급가,if(prd.TAXFREE_YN='1',0,round(item.order_sel_costpr*1.1)-item.order_sel_costpr) 부가세
          ,if(ord.wtid is null  or ord.wtid ='','deposit',if(INSTR(ord.wtid,'VBNK'),'vbank','card')) 카드결제여부,
          cust.DELIV_POSITION as 성수동,cust.admin_name as 매칭영업사원
          FROM TB_ORDER_ITEM item
          right join TB_PROD prd on item.PROD_CD = prd.PROD_CD join TB_ORDER_COND_CD ord_cond_cd
          on item.order_cond_cd = ord_cond_cd.ORDER_COND_CD join TB_SELLER sel on item.SELLER_ID = sel.SELLER_ID
          join TB_ORDER ord on ord.order_no = item.order_no
          inner join (SELECT ct.*,adn.admin_name from TB_CUST ct left join TB_ADMIN adn on ct.admin_id = adn.admin_id) cust on ord.cust_id = cust.cust_id
          WHERE $date_format $seller $cust $order_cond_cd $tx_yn $limit");
          //if(prd.TAXFREE_YN='1',item.order_pay,item.order_costpr) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay-item.order_costpr) 부가세
          //if(prd.TAXFREE_YN='1',item.order_pay,item.order_pay/1.1) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay/11) 부가세
        // if ($mm == "00") {show cloar! mon
        //   $stmt->bind_param("s",$y);
        // }else {
        //   $stmt->bind_param("ss",$y,$mm);
        // }
        // $stmt->bind_param("s",$date);
        $stmt->execute();
        $stmt->store_result();
        // echo "검색된 행 갯수 : $stmt->num_rows"."개";
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }
      ///중소업체 정산을위한 쿼리구성 시작*************************************************************************
      public function orderpay_sel_list_select($date1,$date2,$sel_id,$occd,$yn,$cust_id,$s_point,$list,$admin_type,$admin_id,$sales_select,$sdg_get){
        if (isset($s_point) && isset($list)) {
          $limit = "limit $s_point,$list";
        }else {
          $limit = "";
        }
        // echo "$y,$mm,$sel_id,$occd,$yn,$cust_id";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   if ($dd == "00") {
        //     $date = "$y"."-"."$mm";
        //     $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        //   }else {
        //     $date = "$y"."-"."$mm"."-"."$dd";
        //     $date_format = "date_format(ord.ORDER_DATE,'%Y-%m-%d') = ?";
        //   }
        // }
        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
        if ($sel_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$sel_id%'";
        }
        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }
        if ($occd == "ALL") {
          $order_cond_cd= "";
        }else {
          $order_cond_cd = "and item.order_cond_cd = $occd";
        }
        if ($yn == 2) {
          $tx_yn = "group by item.ORDER_ITEM_NO order by ord.order_date asc,item.ORDER_ITEM_NO DESC";
        }else {
          $tx_yn = "and prd.TAXFREE_YN = $yn group by item.ORDER_ITEM_NO order by ord.order_date asc,item.ORDER_ITEM_NO DESC ";
        }
        // echo "날짜 : $date";
        // echo "셀러 : $sel_id";
        // echo "상태 : $occd";
        // echo "면세 : $yn";
        if ($admin_type == "SALES") {
          $admin_type_join = " join (SELECT * from TB_ADMIN where admin_id = '$admin_id') admin
          on cust.admin_id = admin.admin_id";
        }else {
          $admin_type_join = "";
        }
        if ($sales_select == "ALL") {
          $sales_select_where = "";
        }elseif ($sales_select == "NONE") {
          $sales_select_where = " and cust.admin_name is null ";
        }else {
          $sales_select_where = " and cust.admin_id ='$sales_select' ";
        }
        //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
        }else if($sdg_get == "ALL"){
          $sdg_get_where = "";
        }else if($sdg_get == "sdg"){
          $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
          $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
        $stmt = $this->conn->prepare("SELECT item.order_item_no 아이템번호,item.coupon_price 쿠폰할인액,ord.ORDER_DATE 주문날짜,ord.ORDER_NO 주문번호,
          item.SELLER_ID 유통아이디,sel.SELLER_NAME 유통사명,ord.CUST_ID 식당아이디,
          cust.BUSINESS_NAME 식당명,cust.OWNER_NAME 식당대표자명, item.PROD_CD MS상품코드,item.SELLER_PROD_CD 유통사상품코드,
          prd.PROD_NAME 상품명,prd.PROD_CONT 상품내용,prd.PROD_WGT 상품중량,prd.FACT_NAME 생산지,
          prd.TAXFREE_YN 면세코드,if(prd.TAXFREE_YN='1','면세','과세') 면세여부,prd.STN_COND_CD 보관상태,
          item.ORDER_DEADLINE_TM 배송기간, item.order_cond_cd 주문상태코드,
          ord_cond_cd.ORDER_COND_NAME 주문상태, if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) 상품금액,
          item.PROD_ORDER_CNT 주문수량 ,(if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) * item.prod_order_cnt) 상품금액X주문수량,
          item.order_sel_costpr 공급가,if(prd.TAXFREE_YN='1',0,round(item.order_sel_costpr*1.1)-item.order_sel_costpr) 부가세,
          if(prd.TAXFREE_YN='1',(item.order_sel_costpr * item.prod_order_cnt),(item.order_sel_costpr * item.prod_order_cnt)) 총공급가,
          if(prd.TAXFREE_YN='1',0,((round(item.order_sel_costpr*1.1)-item.order_sel_costpr) * item.prod_order_cnt)) 총부가세,
          if(ord.wtid is null  or ord.wtid ='','deposit',if(INSTR(ord.wtid,'VBNK'),'vbank','card')) 카드결제여부,'' 쿠폰번호,
          coupon_his.COUPON_DISCOUNT_PRICE 쿠폰금액,
          cust.DELIV_POSITION as 성수동,cust.admin_name as 매칭영업사원,ord.wtid as WTID,item.arrive_date as 입고예정일,
          soi.SELLER_ORDER_NAME as 매입처주문명,memo.memo
          FROM TB_ORDER_ITEM item
          left join TB_COUPON_HIS coupon_his on item.ORDER_NO = coupon_his.ORDER_NO and item.SELLER_ID = coupon_his.SELLER_ID
          right join TB_PROD prd on item.PROD_CD = prd.PROD_CD join TB_ORDER_COND_CD ord_cond_cd
          on item.order_cond_cd = ord_cond_cd.ORDER_COND_CD
          join (SELECT * from TB_SELLER where seller_id
          not in (
            '1018130747','1078176324','1258544565',
            '1248531373','3128125280','6038111270',
            '6798500934','deliverylab')) sel on item.SELLER_ID = sel.SELLER_ID
          join TB_ORDER ord on ord.order_no = item.order_no
          inner join (SELECT ct.*,adn.admin_name from TB_CUST ct left join TB_ADMIN adn on ct.admin_id = adn.admin_id) cust on ord.cust_id = cust.cust_id
          left join TB_SELLER_ORDER_INFO soi on ord.CUST_ID = soi.CUST_ID and item.SELLER_ID = soi.SELLER_ID
          left join (SELECT ORDER_NO,SELLER_ID,memo FROM TB_CUST_MEMO where memo is not null and memo != '') as memo
          on ord.ORDER_NO = memo.ORDER_NO and item.SELLER_ID = memo.SELLER_ID
          $admin_type_join
          WHERE $date_format $seller $cust $order_cond_cd $sales_select_where $sdg_get_where $tx_yn $limit");
          //if(prd.TAXFREE_YN='1',item.order_pay,item.order_costpr) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay-item.order_costpr) 부가세
          //if(prd.TAXFREE_YN='1',item.order_pay,item.order_pay/1.1) 공급가,if(prd.TAXFREE_YN='1',0,item.order_pay/11) 부가세
        // if ($mm == "00") {
        //   $stmt->bind_param("s",$y);
        // }else {
        //   $stmt->bind_param("ss",$y,$mm);
        // }
        // $stmt->bind_param("s",$date);
        $stmt->execute();
        $stmt->store_result();
        // echo "검색된 행 갯수 : $stmt->num_rows"."개";
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }

      public function admin_deposit_sel_order_pay($date1,$date2,$type,$seller_id,$cust_id,$tx,$admin_type,$admin_id,$sales_select,$sdg_get)
      {
        // echo "$date1,$date2,$type,$seller_id,$cust_id,$tx";
        if ($tx == 2) {
          $yn = "";
          $coupon_price = "sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)";
        }else{
          $yn = "and prd.TAXFREE_YN = $tx";
          // $coupon_price = "sum(item.order_pay * item.prod_order_cnt)";
          $coupon_price = "sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)";
        }
        // echo "pay : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }

        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";

        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }


        if ($admin_type == "SALES") {
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = "";
          }
          $admin_type_join = " join
          (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
          on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id' $sdg_get_where)
          cust on ord.cust_id = cust.cust_id";
        }else {
          $admin_type_join = "";
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = "";
          }
          if ($sales_select == "ALL") {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where 1 $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else if($sales_select == "NONE"){
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where admin.admin_name is null $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where cust.admin_id = '$sales_select' $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }
          //정산관리 검색필터추가!
        }


          // $stmt = $this->conn->prepare("SELECT sum(item.order_pay * item.prod_order_cnt) 상품금액X주문수량
          //   FROM TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd  WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn");
          $stmt = $this->conn->prepare("SELECT sum(wow.상품금액X주문수량) from
          (SELECT 'group' as gp,if(coupon.COUPON_DISCOUNT_PRICE is null,sum(if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) * item.prod_order_cnt),
          sum(if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) * item.prod_order_cnt)) 상품금액X주문수량
          FROM (SELECT * from TB_ORDER_ITEM where seller_id
          not in (
            '1018130747','1078176324','1258544565',
            '1248531373','3128125280','6038111270',
            '6798500934','deliverylab')) item join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd
          left join (SELECT * from TB_COUPON_HIS group by order_no,seller_id) coupon on ord.ORDER_NO = coupon.ORDER_NO
          and item.SELLER_ID = coupon.SELLER_ID $admin_type_join
          WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn group by ord.ORDER_NO,item.SELLER_ID) wow group by gp");
          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_deposit_sel_order_row($date1,$date2,$type,$seller_id,$cust_id,$tx,$admin_type,$admin_id,$sales_select,$sdg_get)



      {
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "row : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }

        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";

        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }

        if ($admin_type == "SALES") {
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = "";
          }
          $admin_type_join = " join
          (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
          on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id' $sdg_get_where)
          cust on ord.cust_id = cust.cust_id";
        }else {
          $admin_type_join = "";
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = "";
          }
          if ($sales_select == "ALL") {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where 1 $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else if($sales_select == "NONE"){
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where admin.admin_name is null $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where cust.admin_id = '$sales_select' $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }
          //정산관리 검색필터추가!
        }

          $stmt = $this->conn->prepare("SELECT item.order_no
            FROM (SELECT * from TB_ORDER_ITEM where seller_id
            not in (
              '1018130747','1078176324','1258544565',
              '1248531373','3128125280','6038111270',
              '6798500934','deliverylab')) item  join TB_ORDER ord on item.order_no = ord.order_no
            left join TB_PROD prd on item.prod_cd = prd.prod_cd $admin_type_join
            WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn
            group by item.order_no,item.seller_id");

          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_deposit_sel_order_cnt($date1,$date2,$type,$seller_id,$cust_id,$tx,$admin_type,$admin_id,$sales_select,$sdg_get)
      {
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "cnt : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }

        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";

        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }

        if ($admin_type == "SALES") {
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = "";
          }
          $admin_type_join = " join
          (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
          on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id' $sdg_get_where)
          cust on ord.cust_id = cust.cust_id";
        }else {
          $admin_type_join = "";
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = "";
          }
          if ($sales_select == "ALL") {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where 1 $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else if($sales_select == "NONE"){
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where admin.admin_name is null $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where cust.admin_id = '$sales_select' $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }
          //정산관리 검색필터추가!
        }
          $stmt = $this->conn->prepare("SELECT item.order_no
            FROM (SELECT * from TB_ORDER_ITEM where seller_id
            not in (
              '1018130747','1078176324','1258544565',
              '1248531373','3128125280','6038111270',
              '6798500934','deliverylab')) item  join TB_ORDER ord on item.order_no = ord.order_no
            left join TB_PROD prd on item.prod_cd = prd.prod_cd $admin_type_join
            WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn");
          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }
      public function select_GroupDELIV_POSITION()
      {
        $stmt = $this->conn->prepare("SELECT DELIV_POSITION FROM TB_CUST where DELIV_POSITION is not null and DELIV_POSITION != '' group by DELIV_POSITION  order by DELIV_POSITION asc");
        // $stmt->bind_param("s",$date);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }
      ///중소업체 정산을위한 쿼리구성 끝*************************************************************************
      public function admin_deposit_order_pay($date1,$date2,$type,$seller_id,$cust_id,$tx,$admin_type,$admin_id,$sales_select,$sdg_get)
      {
        // echo "$date1,$date2,$type,$seller_id,$cust_id,$tx";
        if ($tx == 2) {
          $yn = "";
          $coupon_price = "sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)";
        }else{
          $yn = "and prd.TAXFREE_YN = $tx";
          // $coupon_price = "sum(item.order_pay * item.prod_order_cnt)";
          $coupon_price = "sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)";
        }
        // echo "pay : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }
        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }
        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }
        if ($admin_type == "SALES") {
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "ALL"){
            $sdg_get_where = "";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
          $admin_type_join = " join
          (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
          on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id' $sdg_get_where)
          cust on ord.cust_id = cust.cust_id";
        }else {
          $admin_type_join = "";
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "ALL"){
            $sdg_get_where = "";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
          if ($sales_select == "ALL") {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where 1 $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else if($sales_select == "NONE"){
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where admin.admin_name is null $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where cust.admin_id = '$sales_select' $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }
          //정산관리 검색필터추가!
        }
          // $stmt = $this->conn->prepare("SELECT sum(item.order_pay * item.prod_order_cnt) 상품금액X주문수량
          //   FROM TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd  WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn");
          $stmt = $this->conn->prepare("SELECT sum(wow.상품금액X주문수량) from
          (SELECT 'group' as gp,if(coupon.COUPON_DISCOUNT_PRICE is null,sum(item.order_pay * item.prod_order_cnt),
          sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)) 상품금액X주문수량
          FROM TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd
          left join (SELECT * from TB_COUPON_HIS group by order_no,seller_id) coupon on ord.ORDER_NO = coupon.ORDER_NO
          and item.SELLER_ID = coupon.SELLER_ID $admin_type_join
          WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn group by ord.ORDER_NO,item.SELLER_ID) wow group by gp");
          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }
      public function admin_deposit_order_row($date1,$date2,$type,$seller_id,$cust_id,$tx,$admin_type,$admin_id,$sales_select,$sdg_get)
      {
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "row : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }
        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }
        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }
        if ($admin_type == "SALES") {
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "ALL"){
            $sdg_get_where = "";
          }else {
            $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
          $admin_type_join = " join
          (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
          on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id' $sdg_get_where)
          cust on ord.cust_id = cust.cust_id";
        }else {
          $admin_type_join = "";
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "ALL"){
            $sdg_get_where = "";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
          if ($sales_select == "ALL") {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where 1 $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else if($sales_select == "NONE"){
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where admin.admin_name is null $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where cust.admin_id = '$sales_select' $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }
          //정산관리 검색필터추가!
        }
          $stmt = $this->conn->prepare("SELECT DISTINCT item.order_no
            FROM TB_ORDER_ITEM item  join TB_ORDER ord on item.order_no = ord.order_no
            left join TB_PROD prd on item.prod_cd = prd.prod_cd $admin_type_join
            WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn
            group by item.order_no,item.seller_id");
          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }
      public function admin_deposit_order_cnt($date1,$date2,$type,$seller_id,$cust_id,$tx,$admin_type,$admin_id,$sales_select,$sdg_get)
      {
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "cnt : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }
        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }
        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }
        if ($admin_type == "SALES") {
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "ALL"){
            $sdg_get_where = "";
          }else {
            $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
          $admin_type_join = " join
          (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
          on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id' $sdg_get_where)
          cust on ord.cust_id = cust.cust_id";
        }else {
          $admin_type_join = "";
          //정산관리 검색필터추가!
          if ($sdg_get == "basic") {
            $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
          }else if($sdg_get == "ALL"){
            $sdg_get_where = "";
          }else if($sdg_get == "sdg"){
            $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
          }else {
            $sdg_get_where = " and cust.DELIV_POSITION like '$sdg_get' ";
          }
          if ($sales_select == "ALL") {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where 1 $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else if($sales_select == "NONE"){
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where admin.admin_name is null $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }else {
            $admin_type_join = " join
            (SELECT cust.* from TB_CUST cust left join TB_ADMIN admin
            on cust.admin_id = admin.admin_id where cust.admin_id = '$sales_select' $sdg_get_where)
            cust on ord.cust_id = cust.cust_id";
          }
          //정산관리 검색필터추가!
        }
          $stmt = $this->conn->prepare("SELECT item.order_no
            FROM TB_ORDER_ITEM item  join TB_ORDER ord on item.order_no = ord.order_no
            left join TB_PROD prd on item.prod_cd = prd.prod_cd $admin_type_join
            WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn");

          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      //_sellerveiw 시작//
      public function admin_deposit_order_pay_sellerveiw($date1,$date2,$type,$seller_id,$cust_id,$tx)
      {
        // echo "$date1,$date2,$type,$seller_id,$cust_id,$tx";
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "pay : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }

        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";

        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }





          $stmt = $this->conn->prepare("SELECT sum(if(prd.TAXFREE_YN='1',item.order_sel_costpr,round(item.order_sel_costpr*1.1)) * item.prod_order_cnt) 상품금액X주문수량
            FROM TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd  WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn");
          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_deposit_order_row_sellerveiw($date1,$date2,$type,$seller_id,$cust_id,$tx)
      {
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "row : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }

        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";

        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }

          $stmt = $this->conn->prepare("SELECT item.order_no
            FROM TB_ORDER_ITEM item  join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd  WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn group by item.order_no,item.seller_id");

          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function admin_deposit_order_cnt_sellerveiw($date1,$date2,$type,$seller_id,$cust_id,$tx)
      {
        if ($tx == 2) {
          $yn = "";
        }elseif (condition) {
          $yn = "and prd.TAXFREE_YN = $tx";
        }
        // echo "cnt : $y,$mm,$type,$seller_id,$cust_id,$tx</br>";
        // if ($mm == "00") {
        //   $date = "$y";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y') = ?";
        // }else {
        //   $date = "$y"."-"."$mm";
        //   $date_format = "date_format(ord.ORDER_DATE,'%Y-%m') = ?";
        // }

        $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";

        if ($seller_id == "ALL") {
          $seller = "";
        }else {
          $seller = "and item.SELLER_ID like '%$seller_id%'";
        }

        if ($cust_id == "ALL") {
          $cust = "";
        }else {
          // $cust = "and cust.BUSINESS_NAME like '%$cust_id%'";
          $cust = "and ord.CUST_ID like '%$cust_id%'";
        }
          $stmt = $this->conn->prepare("SELECT item.order_no
            FROM TB_ORDER_ITEM item  join TB_ORDER ord on item.order_no = ord.order_no left join TB_PROD prd on item.prod_cd = prd.prod_cd  WHERE $date_format and item.order_cond_cd ='$type' $seller $cust $yn");

          // $stmt->bind_param("s",$date);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }
      //_sellerveiw 끝//

      public function insertTB_SELLER($seller_id,$seller_name,$addr_cd,$addr_cont,$tel_no)
      {
        $stmt = $this->conn->prepare("INSERT INTO TB_SELLER(SELLER_ID,SELLER_NAME,ADDR_CD,ADDR_CONT,TEL_NO,COSTPR_YN) VALUES (?,?,?,?,?,'Y')");
        $stmt->bind_param("sssss",$seller_id,$seller_name,$addr_cd,$addr_cont,$tel_no);
        if ($stmt->execute()) {
              return INSERT_COMPLETED;
        } else {
              return INSERT_FAILED;
        }
      }

      public function deleteTB_CUST_SEL($seller_id)
      {
        $stmt = $this->conn->prepare("DELETE from TB_CUST where cust_id = ?");
        $stmt->bind_param("s",$seller_id);
        if ($stmt->execute()) {
              return DELETE_COMPLETED;
        } else {
              return DELETE_FAILED;
        }
      }
      public function insertTB_SELLER_Admin($seller_id,$pass,$seller_name)
      {
        $password = md5($pass);
        $stmt = $this->conn->prepare("INSERT IGNORE INTO TB_ADMIN (ADMIN_ID, PASSWORD, ADMIN_NAME, REG_DATE, ADMIN_TYPE) VALUES (?,?,?, now(),'SELLER')");
        $stmt->bind_param("sss",$seller_id,$password,$seller_name);
        if ($stmt->execute()) {
              return INSERT_COMPLETED;
        } else {
              return INSERT_FAILED;
        }
      }




      public function select_metching_seller_list($cust_id,$fav)
      {

        if ($fav == "fav") {
        $joinFav = "join TB_FAVOR_PROD fav on sel_cust.cust_id = fav.cust_id and sel.seller_id = fav.seller_id";
      }else {
        $joinFav = "";
      }
          $stmt = $this->conn->prepare("SELECT sel_cust.CUST_ID,sel_cust.SELLER_ID,MARGIN_RATE,sel.seller_name FROM TB_SELLER_BY_CUST sel_cust join TB_SELLER sel on sel_cust.seller_id = sel.seller_id $joinFav WHERE sel_cust.cust_id = ?");
          $stmt->bind_param("s",$cust_id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function select_item_prod_count($order_no,$seller_id,$prod_cd)
      {
          $stmt = $this->conn->prepare("SELECT count(*) FROM TB_ORDER_ITEM WHERE order_no=? and seller_id = ? and prod_cd = ?");
          $stmt->bind_param("iss",$order_no,$seller_id,$prod_cd);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function updatebenefit_yn($benefit_yn,$cust_id)
      {
        $stmt = $this->conn->prepare("UPDATE TB_CUST SET BENEFIT_YN =? WHERE CUST_ID = ?");
        $stmt->bind_param("ss",$benefit_yn,$cust_id);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }

      public function insert_prback($prod_cd,$sel_id,$sel_cd,$ord_pr,$ord_tm)
      {
        $stmt = $this->conn->prepare("INSERT INTO TB_PRICE_BACK(PROD_CD,SELLER_ID,SELLER_PROD_CD,SELLER_PROD_PRICE,ORDER_DEADLINE_TM,reg_date) VALUES (?,?,?,?,?,now())");
        $stmt->bind_param("sssii",$prod_cd,$sel_id,$sel_cd,$ord_pr,$ord_tm);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }

      public function update_prback($sel_id,$sel_cd,$ord_pr,$ord_tm)
      {
        $stmt = $this->conn->prepare("UPDATE TB_PRICE_BACK SET SELLER_PROD_PRICE=?,ORDER_DEADLINE_TM=?,reg_date=now() WHERE SELLER_ID=? and SELLER_PROD_CD=?");
        $stmt->bind_param("iiss",$ord_pr,$ord_tm,$sel_id,$sel_cd);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }

      public function update_sel_cd($sel_id,$sel_cd,$sel_pr,$tm,$point)
      {
        $stmt = $this->conn->prepare("UPDATE TB_SELLER_PROD_PRICE SET SELLER_PROD_PRICE=?,ORDER_DEADLINE_TM=?,POINT_ORDER_YN=? WHERE  SELLER_ID=? and SELLER_PROD_CD=?");
        $stmt->bind_param("iiiss",$sel_pr,$tm,$point,$sel_id,$sel_cd);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }

      public function update_sel_cd_one($sel_id,$sel_cd,$sel_pr)
      {
        $stmt = $this->conn->prepare("UPDATE TB_SELLER_PROD_PRICE SET SELLER_PROD_PRICE=? WHERE  SELLER_ID=? and SELLER_PROD_CD=?");
        $stmt->bind_param("iss",$sel_pr,$sel_id,$sel_cd);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }
      public function updateSellerProdName($req)
      {

        $sel_id = $req["sel_id"];//유통사아이디
        $sel_prod_cd = $req["sel_prod_cd"];//유통사상품코드
        $sel_prod_name = $req["sel_prod_name"];//유통사상품
        $stmt = $this->conn->prepare("UPDATE TB_SELLER_PROD_PRICE SET SELLER_PROD_NAME = '$sel_prod_name'
          WHERE SELLER_PROD_CD = '$sel_prod_cd' AND SELLER_ID = '$sel_id'");
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }


      public function update_SelCust_memo($memo,$seller_id)
      {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER SET SELLER_CONT=? WHERE seller_id = ?");
          $stmt->bind_param("ss", $memo,$seller_id);
          if ($stmt->execute()) {
              return UPDATE_COMPLETED;
          } else {
              return UPDATE_FAILED;
          }
      }

      public function select_sf_info($seller_id)
      {
          $stmt = $this->conn->prepare("SELECT STAFF_NO,STAFF_NAME,TEL_NO FROM TB_STAFF WHERE seller_id = ?");
          $stmt->bind_param("s",$seller_id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }

      }


      public function admin_update_margin($margin,$cust_id,$seller_id)
      {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET MARGIN_RATE=? WHERE CUST_ID=? and SELLER_ID=?");
          $stmt->bind_param("iss",$margin,$cust_id,$seller_id);
          if ($stmt->execute()) {
              return UPDATE_COMPLETED;
          } else {
              return UPDATE_FAILED;
          }
      }

      public function admin_update_column($column,$cust_id,$seller_id,$value)
      {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET $column='$value' WHERE CUST_ID=? and SELLER_ID=?");
          $stmt->bind_param("ss",$cust_id,$seller_id);
          if ($stmt->execute()) {
              return UPDATE_COMPLETED;
          } else {
              return UPDATE_FAILED;
          }
      }

      public function admin_update_staff($staf_id,$cust_id,$seller_id)
      {
        if ($staf_id=="") {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET STAFF_NO=null WHERE CUST_ID=? and SELLER_ID=?");
          $stmt->bind_param("ss",$cust_id,$seller_id);
        }else {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET STAFF_NO=? WHERE CUST_ID=? and SELLER_ID=?");
          $stmt->bind_param("sss",$staf_id,$cust_id,$seller_id);
        }
          if ($stmt->execute()) {
              return UPDATE_COMPLETED;
          } else {
              return UPDATE_FAILED;
          }
      }

      public function admin_update_staff_all($staf_id,$seller_id)
      {
        if ($staf_id=="") {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET STAFF_NO=null WHERE SELLER_ID=?");
          $stmt->bind_param("s",$seller_id);
        }else {
          $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET STAFF_NO=? WHERE SELLER_ID=?");
          $stmt->bind_param("ss",$staf_id,$seller_id);
        }
          if ($stmt->execute()) {
              return UPDATE_COMPLETED;
          } else {
              return UPDATE_FAILED;
          }
      }

      public function keydown_orderpay_cust($text)
      {
          if ($text == "") {
            $stmt = $this->conn->prepare("SELECT cust.cust_id,cust.business_name from TB_CUST cust left join TB_SELLER sel on cust.cust_id = sel.seller_id  where sel.seller_id is null and cust.cust_id is null  order by cust.business_name asc");
          }else {
            $stmt = $this->conn->prepare("SELECT cust.cust_id,cust.business_name from TB_CUST cust left join TB_SELLER sel on cust.cust_id = sel.seller_id  where sel.seller_id is null and cust.business_name like concat('%',?,'%') order by cust.business_name asc");
            $stmt->bind_param("s",$text);
          }
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }

      }

      // public function admin_update_staff_all($staf_id,$seller_id);
      // {
      //   if ($staf_id=="") {
      //     $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET STAFF_NO=null WHERE SELLER_ID=?");
      //     $stmt->bind_param("s",$seller_id);
      //   }else {
      //     $stmt = $this->conn->prepare("UPDATE TB_SELLER_BY_CUST SET STAFF_NO=? WHERE SELLER_ID=?");
      //     $stmt->bind_param("ss",$staf_id,$seller_id);
      //   }
      //     if ($stmt->execute()) {
      //         return UPDATE_COMPLETED;
      //     } else {
      //         return UPDATE_FAILED;
      //     }
      // }



      public function insertSelMetchingCode($list_date,$seller_id,$list_date2)
      {
        // echo "$list_date,$seller_id,$list_date";
        $stmt = $this->conn->prepare("INSERT INTO TB_SELLER_PROD_CD(PROD_CD,SELLER_ID,SELLER_PROD_CD) VALUES (?,?,?)");
        $stmt->bind_param("sss",$list_date,$seller_id,$list_date2);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }

      public function insertSelMetchingPrice($prod_cd,$seller_id,$prod_all,$price,$tm)
      {
        // echo "$prod_cd,$seller_id,$prod_all,$price,$tm";
        // echo "코드,아이디,내용,가격,날짜";
        if ($seller_id == "1184000627") {
          $stmt = $this->conn->prepare("INSERT INTO TB_SELLER_PROD_PRICE(SELLER_PROD_CD,SELLER_ID,SELLER_PROD_NAME,SELLER_PROD_PRICE,ORDER_DEADLINE_TM,POINT_ORDER_YN) VALUES (?,?,?,?,?,1)");
        }else {
          $stmt = $this->conn->prepare("INSERT INTO TB_SELLER_PROD_PRICE(SELLER_PROD_CD,SELLER_ID,SELLER_PROD_NAME,SELLER_PROD_PRICE,ORDER_DEADLINE_TM) VALUES (?,?,?,?,?)");
        }
        $stmt->bind_param("sssii",$prod_cd,$seller_id,$prod_all,$price,$tm);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }
        //=19년07월27일@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
      // SELECT GROUP_CONCAT(prod_name, ",", prod_cont, ",", prod_wgt, ",", FACT_NAME) AS hero_string FROM TB_PROD WHERE prod_cd = "L0110023"

      public function selectsitelist($cust_id)
      {
          $stmt = $this->conn->prepare("SELECT cust_id,TEMPERATURE_PROD_STN_POSITION_IMG,REFRIGERATION_PROD_STN_POSITION_IMG,site_key_position_img,site_cont,FREEZE_PROD_STN_POSITION_IMG,FIRST_SITE_POSITION_IMG,SECOND_SITE_POSITION_IMG FROM TB_SITE WHERE cust_id=?");
          $stmt->bind_param("s",$cust_id);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function selectTermsLimit($terms_class_cd)
      {
          $stmt = $this->conn->prepare("SELECT ts.terms_no,ts.title,ts.info,ts_cd.terms_class_name,ts.reg_date FROM (SELECT *  from TB_TERMS WHERE terms_class_cd = ?) ts join TB_TERMS_CLASS_CD ts_cd on ts.terms_class_cd = ts_cd.terms_class_cd order by ts.terms_no desc LIMIT 1");
          $stmt->bind_param("s",$terms_class_cd);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function selectTermsSelect($terms_no)
      {
          $stmt = $this->conn->prepare("SELECT ts.terms_no,ts.title,ts.info,ts.terms_class_cd,ts_cd.terms_class_name,ts.reg_date FROM TB_TERMS ts join TB_TERMS_CLASS_CD ts_cd on ts.terms_class_cd = ts_cd.terms_class_cd  WHERE ts.terms_no =?");
          $stmt->bind_param("i",$terms_no);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function selectTerms($text)
      {
        if ($text == "ALL" || $text == "") {
          $where = "";
        }else {
          $where = "WHERE ts.terms_class_cd = '$text'";
        }
        // echo "$where";
          $stmt = $this->conn->prepare("SELECT ts.terms_no,ts.title,ts.info,ts_cd.terms_class_name,ts.reg_date FROM TB_TERMS ts join TB_TERMS_CLASS_CD ts_cd on ts.terms_class_cd = ts_cd.terms_class_cd $where  order by ts.terms_no desc");
          // $stmt->bind_param("s",$text);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function insertTerms($title,$info,$terms_class_cd)
      {
        // echo "$prod_cd,$seller_id,$prod_all,$price,$tm";
        // echo "코드,아이디,내용,가격,날짜";
        $stmt = $this->conn->prepare("INSERT INTO TB_TERMS(title,info,terms_class_cd,reg_date) VALUES (?,?,?,now())");
        $stmt->bind_param("sss",$title,$info,$terms_class_cd);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }

      public function insertProdSerach($cust_id,$search_name,$search_yn){
        $stmt = $this->conn->prepare("INSERT INTO TB_PROD_SEARCH_INFO(cust_id,search_name,reg_date,search_yn) VALUES (?,?,now(),?)");
        $stmt->bind_param("sss",$cust_id,$search_name,$search_yn);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }

      public function updateProdSerach($prod_cd,$cust_id,$no){
        $stmt = $this->conn->prepare("UPDATE TB_PROD_SEARCH_INFO set prod_cd = ? where cust_id = ? and no = ?");
        $stmt->bind_param("ssi",$prod_cd,$cust_id,$no);
        if ($stmt->execute()) {
            return UPDATE_COMPLETED;
        } else {
            return UPDATE_FAILED;
        }
      }

      public function selectProdSerachlimit($cust_id){
        $stmt = $this->conn->prepare("SELECT no from TB_PROD_SEARCH_INFO where cust_id = ? order by no desc LIMIT 1");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }

      public function amdin_msdb_download($class_cd)
      {
        if (!isset($class_cd) || empty($class_cd) || $class_cd == "") {
          $class_cd_where = 1;
        }else {
          $class_cd_where = "left(cc.class_cd,1) = '$class_cd'";
        }
            $stmt = $this->conn->prepare("SELECT prd.PROD_CD as 상품코드,
                                          case
                                            when left(cc.class_cd,1) = 'A' then '농산'
                                            when left(cc.class_cd,1) = 'F' then '수산'
                                            when left(cc.class_cd,1) = 'L' then '축산'
                                            when left(cc.class_cd,1) = 'P' then '공산'
                                            when left(cc.class_cd,1) = 'G' then '잡화'
                                          else '에러' end as 대분류
                                            ,cc.CLASS_NAME as 분류명,ccd.CLASS_NAME as 분류상세코드,prd.PROD_NAME as 상품명,prd.PROD_CONT as 상품설명
                                            ,prd.PROD_WGT as 상품중량,prd.FACT_NAME as 생산자명,if(prd.TAXFREE_YN = 1,'Y','N') as 면세여부,cond.STN_COND_name as 상태,
                                            (SELECT cd.SELLER_PROD_CD from TB_SELLER_PROD_CD cd where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1258544565') as 삼성아이디,
                                            (SELECT pic.seller_prod_price from (SELECT * from TB_SELLER_PROD_CD where SELLER_ID = '1258544565')cd
                                            join (SELECT * from TB_SELLER_PROD_PRICE where SELLER_ID = '1258544565') pic on cd.seller_prod_cd = pic.seller_prod_cd
                                              where cd.PROD_CD = prd.prod_cd) as 삼성단가,
                                            (SELECT cd.SELLER_PROD_CD from TB_SELLER_PROD_CD cd where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '6038111270') as CJ아이디,
                                            (SELECT pic.seller_prod_price from (SELECT * from TB_SELLER_PROD_CD where SELLER_ID = '6038111270') cd
                                            join (SELECT * from TB_SELLER_PROD_PRICE where SELLER_ID = '6038111270') pic on cd.seller_prod_cd = pic.seller_prod_cd
                                              where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '6038111270') as CJ단가,
                                            (SELECT cd.SELLER_PROD_CD from TB_SELLER_PROD_CD cd where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1018130747') as 한화아이디,
                                            (SELECT pic.seller_prod_price from (SELECT * from TB_SELLER_PROD_CD where SELLER_ID = '1018130747') cd
                                            join (SELECT * from TB_SELLER_PROD_PRICE where SELLER_ID = '1018130747') pic on cd.seller_prod_cd = pic.seller_prod_cd
                                              where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1018130747') as 한화단가,
                                            (SELECT cd.SELLER_PROD_CD from TB_SELLER_PROD_CD cd where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1248531373') as 현대아이디,
                                            (SELECT pic.seller_prod_price from (SELECT * from TB_SELLER_PROD_CD where SELLER_ID = '1248531373') cd
                                            join (SELECT * from TB_SELLER_PROD_PRICE where SELLER_ID = '1248531373') pic on cd.seller_prod_cd = pic.seller_prod_cd
                                              where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1248531373') as 현대단가,
                                            (SELECT cd.SELLER_PROD_CD from TB_SELLER_PROD_CD cd where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '3128125280') as 동원아이디,
                                            (SELECT pic.seller_prod_price from (SELECT * from TB_SELLER_PROD_CD where SELLER_ID = '3128125280') cd
                                            join (SELECT * from TB_SELLER_PROD_PRICE where SELLER_ID = '3128125280') pic on cd.seller_prod_cd = pic.seller_prod_cd
                                              where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '3128125280') as 동원단가,
                                            (SELECT cd.SELLER_PROD_CD from TB_SELLER_PROD_CD cd where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1078176324') as 아워홈아이디,
                                            (SELECT pic.seller_prod_price from (SELECT * from TB_SELLER_PROD_CD where SELLER_ID = '1078176324') cd
                                            join (SELECT * from TB_SELLER_PROD_PRICE where SELLER_ID = '1078176324') pic on cd.seller_prod_cd = pic.seller_prod_cd
                                              where cd.PROD_CD = prd.prod_cd and cd.SELLER_ID = '1078176324') as 아워홈단가
                                            FROM TB_PROD prd join TB_CLASS_CD cc on prd.class_cd = cc.class_cd left join TB_CLASS_CD ccd on prd.CLASS_DETAIL_CD = ccd.class_cd join TB_STN_COND cond on prd.STN_COND_CD = cond.STN_COND_CD
                                            where $class_cd_where
                                            ORDER BY find_in_set(대분류,'농산,수산,축산,공산,잡화'),cc.class_name,ccd.class_name,prd.prod_name,prd.prod_wgt asc");
          // $stmt->bind_param("s",$class_cd);
          $stmt->execute();
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              return $stmt;
          } else {
              return SELECT_FAILED;
          }
      }

      public function select_my_coupon($cust_id){
        $stmt = $this->conn->prepare("SELECT coupon_main.COUPON_NO,coupon_main.COUPON_CLASS_CD,coupon.COUPON_START_TM,
          coupon.COUPON_END_TM,coupon_main.COUPON_USE_YN,coupon_main.COUPON_REG_DATE,
          coupon_main.COUPON_DISCOUNT_RATE,coupon.COUPON_CLASS_NAME,coupon.COUPON_BENEFIT FROM TB_COUPON coupon_main join TB_COUPON_CLASS_CD coupon
          on coupon_main.COUPON_CLASS_CD = coupon.COUPON_CLASS_CD
          WHERE coupon_main.COUPON_USE_YN = '0' and coupon_main.cust_id = ?");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }

      public function select_hero_coupon($cust_id){
        $stmt = $this->conn->prepare("SELECT coupon.COUPON_CLASS_CD,coupon.COUPON_CLASS_NAME,coupon.COUPON_CONT,
          coupon.COUPON_BENEFIT,coupon_main.coupon_no,coupon.coupon_start_tm,coupon.coupon_end_tm FROM TB_COUPON_CLASS_CD coupon left join (SELECT * from TB_COUPON where cust_id = ?) coupon_main
          on coupon.COUPON_CLASS_CD = coupon_main.COUPON_CLASS_CD WHERE coupon.COUPON_ACTIV_YN = '1'");
        $stmt->bind_param("s",$cust_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }

      public function select_admin_coupon($order_by){
        if ($order_by == "desc") {
          $order_by_str = "order by coupon.COUPON_DISCOUNT_PRICE $order_by";
        }else if($order_by == "asc") {
          $order_by_str = "order by coupon.COUPON_DISCOUNT_PRICE $order_by";
        }else {
          $order_by_str = "";
        }
        $stmt = $this->conn->prepare("SELECT coupon.COUPON_CLASS_CD,coupon.COUPON_CLASS_NAME,coupon.COUPON_CONT,
          coupon.COUPON_BENEFIT,coupon.COUPON_ACTIV_YN,coupon.COUPON_USE_STIP,coupon.COUPON_DISCOUNT_PRICE FROM TB_COUPON_CLASS_CD coupon $order_by_str");
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }

      public function select_admin_coupon_detail($coupon_class_cd){
        $stmt = $this->conn->prepare("SELECT coupon.COUPON_CLASS_CD,coupon.COUPON_CLASS_NAME,coupon.COUPON_CONT,
          coupon.COUPON_BENEFIT,coupon.COUPON_ACTIV_YN,coupon.coupon_start_tm,coupon.coupon_end_tm,coupon.COUPON_USE_STIP,coupon.COUPON_DISCOUNT_PRICE
          FROM TB_COUPON_CLASS_CD coupon where COUPON_CLASS_CD =?");
        $stmt->bind_param("s",$coupon_class_cd);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return $stmt;
        } else {
            return SELECT_FAILED;
        }
      }
      //COUPON_START_TM,COUPON_END_TM,
      public function insert_coupon_user($cust_id,$coupon_class_cd){
        $stmt = $this->conn->prepare("INSERT INTO TB_COUPON(CUST_ID,COUPON_REG_DATE,COUPON_CLASS_CD) VALUES(?,now(),?)");
        $stmt->bind_param("ss",$cust_id,$coupon_class_cd);
        if ($stmt->execute()) {
            return INSERT_COMPLETED;
        } else {
            return INSERT_FAILED;
        }
      }


          public function select_heropay($cust_id)
          {
              $stmt = $this->conn->prepare("SELECT HEROPAY_ID,HEROPAY_CD,REG_DATE FROM TB_HEROPAY WHERE cust_id = ?");
              $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }

          }


          public function insert_heropay($cust_id,$heropay_id,$heropay_ci)
          {
            $stmt = $this->conn->prepare("INSERT INTO TB_HEROPAY(CUST_ID,HEROPAY_ID,HEROPAY_CD,REG_DATE) VALUES(?,?,?,now())");
            $stmt->bind_param("sss",$cust_id,$heropay_id,$heropay_ci);
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
            } else {
                return INSERT_FAILED;
            }
          }

          public function delete_heropay($wpayUserKey)
          {
            $stmt = $this->conn->prepare("DELETE from TB_HEROPAY where HEROPAY_ID =?");
            $stmt->bind_param("s",$wpayUserKey);
            if ($stmt->execute()) {
                return DELETE_COMPLETED;
            } else {
                return DELETE_FAILED;
            }
          }

          public function select_auto_mag($date_first,$date_last,$date1,$date2)
          {
            echo "$date_first,$date_last,$date1,$date2";
            //his.PAYMENT_DATE BETWEEN ? and ? and
            //and g.PAYMENT_DATE BETWEEN ? and ?
            //and his.payment_his_cd != 'BC'
            //and his.payment_his_cd != 'BD' and his.payment_his_cd != 'CC' and his.payment_his_cd != 'SC' and his.payment_his_cd != 'CP' and his.payment_his_cd != 'BC'
             //  $stmt = $this->conn->prepare("SELECT  his.CUST_ID, (sum(his.PAYMENT_PR)+(SELECT IFNULL(sum(g.payment_pr),0) from TB_CUST_PAYMENT_HIS g where g.cust_id=his.CUST_ID
             //  and (g.payment_his_cd = 'BD' or g.payment_his_cd = 'SC' or g.payment_his_cd = 'BC') )) as his_pay,cust.BUSINESS_NAME,cust.OWNER_NAME,cust.TEL_NO,paybln.deposit_bln
             //  FROM TB_CUST_PAYMENT_HIS his join TB_CUST cust on his.cust_id = cust.cust_id join TB_CUST_PAYMENT paybln on cust.cust_id = paybln.cust_id WHERE (his.payment_his_cd = 'BW'  or his.payment_his_cd = 'SI')
             // group by his.cust_id");
             // $stmt = $this->conn->prepare("SELECT  his.CUST_ID, sum(his.PAYMENT_PR) as his_pay,cust.BUSINESS_NAME,cust.OWNER_NAME,cust.TEL_NO,paybln.deposit_bln
             // FROM TB_CUST_PAYMENT_HIS his join TB_CUST cust on his.cust_id = cust.cust_id join TB_CUST_PAYMENT paybln on cust.cust_id = paybln.cust_id group by his.cust_id");
             //원본소스 ----------------------시작
            //  $stmt = $this->conn->prepare("SELECT  his.CUST_ID,
            // (sum(his.per)
            //
            //   +ifnull((
            //     SELECT sum(PAYMENT_PR) as mper from TB_CUST_PAYMENT_HIS where payment_date > '2020-01-01 00:00' and (payment_his_cd = 'BD') and cust_id = his.cust_id
            //   ),0)/*입금액*/
            //
            //   +(
            //     SELECT ifnull(sum(his2.payment_pr),0)
            //     from (SELECT * from TB_CUST_PAYMENT_HIS
            //     where PAYMENT_DATE < '2020-01-01 00:00') his1
            //       join
            //     (SELECT *
            //     from TB_CUST_PAYMENT_HIS
            //     where PAYMENT_DATE > '2020-01-01 00:00'
            //     and (PAYMENT_HIS_CD='SC' or PAYMENT_HIS_CD='BC')) his2
            //
            //     on his1.order_no = his2.order_no
            //     where his2.cust_id=his.cust_id and (his2.PAYMENT_HIS_CD='SC' or his2.PAYMENT_HIS_CD='BC')
            //   )/*상품 취소및 취소접수 금액*/
            // ) as his_pay
            //
            // ,cust.BUSINESS_NAME,cust.OWNER_NAME,cust.TEL_NO,paybln.deposit_bln
            //  FROM
            //  (
            //    SELECT order_no,cust_id,sum(PAYMENT_PR) as per
            //    from TB_CUST_PAYMENT_HIS
            //    where payment_date < '2020-01-01 00:00' group by order_no
            //  ) his/*전날 모든 history*/
            //  join TB_CUST cust on his.cust_id = cust.cust_id join TB_CUST_PAYMENT paybln on cust.cust_id = paybln.cust_id
            //  group by his.cust_id HAVING his_pay > 0");

            // $payment_str = "SELECT * from TB_CUST_PAYMENT_HIS where MONTH(payment_date) < MONTH(now())";
            // $payment_str = "SELECT * from TB_CUST_PAYMENT_HIS where payment_his_cd != 'BD' and  payment_his_cd != 'BB'";
            $payment_str = "SELECT * from TB_CUST_PAYMENT_HIS";
            //**기존 전달 미수금 메세지**//
            // IFNULL(
            //   if((SELECT sum(his.PAYMENT_PR)
            //   FROM ($payment_str) as his
            // join TB_ORDER ord on his.order_no = ord.order_no
            // WHERE  YEAR(payment_date) = YEAR(now()) and MONTH(payment_date) = MONTH(now())
            // and (ord.wtid is null or ord.wtid = '') and his.cust_id = cust.cust_id
            // group by his.cust_id)>0,(pm.DEPOSIT_BLN+(SELECT sum(his.PAYMENT_PR)
            // FROM ($payment_str) as his
            // join TB_ORDER ord on his.order_no = ord.order_no
            // WHERE  YEAR(payment_date) = YEAR(now()) and MONTH(payment_date) = MONTH(now())
            // and (ord.wtid is null or ord.wtid = '') and his.cust_id = cust.cust_id
            // group by his.cust_id)),(pm.DEPOSIT_BLN-(SELECT sum(his.PAYMENT_PR)
            // FROM ($payment_str) as his
            // join TB_ORDER ord on his.order_no = ord.order_no
            // WHERE  YEAR(payment_date) = YEAR(now()) and MONTH(payment_date) = MONTH(now())
            // and (ord.wtid is null or ord.wtid = '') and his.cust_id = cust.cust_id
            // group by his.cust_id)))
            // ,pm.DEPOSIT_BLN)
            //원본소스 끝 ==========================
             $stmt = $this->conn->prepare("SELECT cust.CUST_ID,pm.DEPOSIT_BLN as ifpay,
                cust.BUSINESS_NAME,cust.OWNER_NAME,cust.TEL_NO,pm.DEPOSIT_BLN
                FROM (SELECT * from TB_CUST_PAYMENT where DEPOSIT_BLN< 0) pm
                join (SELECT * from TB_CUST where ACTIV_YN = '1' and cust_id  not like '%herokitchen%') cust on pm.CUST_ID = cust.CUST_ID
                group by cust.CUST_ID
                HAVING ifpay < 0 order by pm.DEPOSIT_BLN");

              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }

          }


          public function select_cancel_payment_pr($order_no,$seller_id)
          {
              $stmt = $this->conn->prepare("SELECT sum(PROD_ORDER_CNT*order_pay),order_cond_cd FROM TB_ORDER_ITEM WHERE order_no = ? and  seller_id = ?");
              $stmt->bind_param("is",$order_no,$seller_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }

          }

          public function selectPD($code)
          {
              $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
              $stmt->bind_param("s",$code);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }

          }

          public function select_order_seller_cond_cd($order_no,$seller_id)
          {
              $stmt = $this->conn->prepare("SELECT order_cond_cd from TB_ORDER_ITEM where order_no = ? and seller_id = ? group by order_cond_cd");
              $stmt->bind_param("is",$order_no,$seller_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }

          }
          public function selectTixBill($fd_date)
          {
              $stmt = $this->conn->prepare("SELECT replace(left(LAST_DAY('$fd_date'),10),'-','') ,cust.cust_id,cust.business_name,cust.owner_name,cust.addr_cont,cust.email  이메일,
sum((item.prod_order_cnt*item.order_pay)-item.coupon_price) 공급가액, right(left(LAST_DAY('$fd_date'),10),2) 일자 ,
pay.status,pay.trade
from TB_CUST cust join (SELECT * from TB_ORDER where wtid is null  or wtid ='') ord on cust.CUST_ID = ord.cust_id
join (SELECT * from TB_ORDER_ITEM where order_cond_cd = '03') item on ord.order_no = item.order_no
join (select * FROM TB_PROD WHERE TAXFREE_YN=1)  prd on item.prod_cd = prd.prod_cd
left join TB_CUST_PAYMENT pay on cust.cust_id = pay.cust_id
where ord.REG_DATE BETWEEN left(LAST_DAY('$fd_date' - interval 1 month),10) + interval 1 day and left(LAST_DAY('$fd_date'),10)
group by cust.cust_id");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function selectElectTixBill($fd_date)
          {
              $stmt = $this->conn->prepare("SELECT replace(left(LAST_DAY('$fd_date'),10),'-','') ,cust.cust_id,cust.business_name,cust.owner_name,cust.addr_cont,cust.email  이메일,
sum(item.prod_order_cnt*item.order_costpr-item.coupon_price) 공급가액,sum(item.prod_order_cnt*(item.order_pay-item.order_costpr)) 과세액, right(left(LAST_DAY('$fd_date'),10),2) 일자,
pay.status,pay.trade
from TB_CUST cust join (SELECT * from TB_ORDER where wtid is null  or wtid ='') ord on cust.CUST_ID = ord.cust_id
join (SELECT * from TB_ORDER_ITEM where order_cond_cd = '03') item on ord.order_no = item.order_no
join (select * FROM TB_PROD WHERE TAXFREE_YN=0)  prd on item.prod_cd = prd.prod_cd
left join TB_CUST_PAYMENT pay on cust.cust_id = pay.cust_id
where ord.REG_DATE BETWEEN left(LAST_DAY('$fd_date' - interval 1 month),10) + interval 1 day and left(LAST_DAY('$fd_date'),10)
group by cust.cust_id");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
//           public function selectTixBill($fd_date)
//           {
//               $stmt = $this->conn->prepare("SELECT replace(left(LAST_DAY('$fd_date' - interval 1 month),10),'-','') ,cust.cust_id,cust.business_name,cust.owner_name,cust.addr_cont,cust.email  이메일,
// sum(item.prod_order_cnt*item.order_pay) 공급가액, right(left(LAST_DAY('$fd_date' - interval 1 month),10),2) 일자 ,
// pay.status,pay.trade
// from TB_CUST cust join (SELECT * from TB_ORDER where wtid is null  or wtid ='') ord on cust.CUST_ID = ord.cust_id
// join (SELECT * from TB_ORDER_ITEM where order_cond_cd = '03') item on ord.order_no = item.order_no
// join (select * FROM TB_PROD WHERE TAXFREE_YN=1)  prd on item.prod_cd = prd.prod_cd
// left join TB_CUST_PAYMENT pay on cust.cust_id = pay.cust_id
// where ord.REG_DATE BETWEEN left(LAST_DAY('$fd_date' - interval 2 month),10) + interval 1 day and left(LAST_DAY('$fd_date' - interval 1 month),10)
// group by cust.cust_id");
//               $stmt->execute();
//               $stmt->store_result();
//               if ($stmt->num_rows > 0) {
//                   return $stmt;
//               } else {
//                   return SELECT_FAILED;
//               }
//           }
//
//           public function selectElectTixBill($fd_date)
//           {
//               $stmt = $this->conn->prepare("SELECT replace(left(LAST_DAY('$fd_date' - interval 1 month),10),'-','') ,cust.cust_id,cust.business_name,cust.owner_name,cust.addr_cont,cust.email  이메일,
// sum(item.prod_order_cnt*item.order_costpr) 공급가액,sum(item.prod_order_cnt*(item.order_pay-item.order_costpr)) 과세액, right(left(LAST_DAY('$fd_date' - interval 1 month),10),2) 일자,
// pay.status,pay.trade
// from TB_CUST cust join (SELECT * from TB_ORDER where wtid is null  or wtid ='') ord on cust.CUST_ID = ord.cust_id
// join (SELECT * from TB_ORDER_ITEM where order_cond_cd = '03') item on ord.order_no = item.order_no
// join (select * FROM TB_PROD WHERE TAXFREE_YN=0)  prd on item.prod_cd = prd.prod_cd
// left join TB_CUST_PAYMENT pay on cust.cust_id = pay.cust_id
// where ord.REG_DATE BETWEEN left(LAST_DAY('$fd_date' - interval 2 month),10) + interval 1 day and left(LAST_DAY('$fd_date' - interval 1 month),10)
// group by cust.cust_id");
//               $stmt->execute();
//               $stmt->store_result();
//               if ($stmt->num_rows > 0) {
//                   return $stmt;
//               } else {
//                   return SELECT_FAILED;
//               }
//           }

          public function updateSta($cust_id,$sta_id,$sta_text){
            $stmt = $this->conn->prepare("UPDATE TB_CUST_PAYMENT SET $sta_id = ? WHERE cust_id = ?");
            $stmt->bind_param("ss",$sta_text,$cust_id);
            if ($stmt->execute()) {
                return UPDATE_COMPLETED;
            } else {
                return UPDATE_FAILED;
            }
          }

          public function admin_order_download($order_no,$seller_id)
          {
              $stmt = $this->conn->prepare("SELECT substr(subdate(now(),interval -(item.ORDER_DEADLINE_TM) day),1,10) as 입고일자,
selcust.cust_cd as 거래처코드,(SELECT seller_prod_cd from TB_SELLER_PROD_CD where prod_cd = item.prod_cd and seller_id = item.seller_id)as 상품코드,
item.prod_order_cnt as 수량,''  as 비고,item.ORDER_DEADLINE_TM as 배송마감일,price.SELLER_PROD_NAME,SUBSTR(item.prod_cd,1,1)  from TB_ORDER_ITEM item left join
TB_ORDER ord on item.order_no = ord.ORDER_NO
left join TB_SELLER_BY_CUST selcust on ord.cust_id = selcust.cust_id and item.seller_id = selcust.seller_id
left join TB_SELLER_PROD_PRICE price on item.SELLER_ID = price.SELLER_ID and (SELECT seller_prod_cd from TB_SELLER_PROD_CD where prod_cd = item.prod_cd and seller_id = item.seller_id) = price.SELLER_PROD_CD
where item.order_no = ? and item.seller_id = ?");
                $stmt->bind_param("is",$order_no,$seller_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function update_point_order_yn($sel_id,$sel_prod_cd,$dom_val){
            // echo "$sel_id,$sel_prod_cd,$dom_val";
            $stmt = $this->conn->prepare("UPDATE TB_SELLER_PROD_PRICE SET POINT_ORDER_YN=? WHERE SELLER_PROD_CD=? and SELLER_ID=?");
            $stmt->bind_param("iss",$dom_val,$sel_prod_cd,$sel_id);
            if ($stmt->execute()) {
                return UPDATE_COMPLETED;
            } else {
                return UPDATE_FAILED;
            }
          }

          public function selectcouponlist($cust_id,$coupon_session_list,$stip,$seller_id)
          {
            $having ="HAVING useall = 0 or useCou REGEXP('$seller_id')";
            if ($coupon_session_list == "") {
              $coupon_session = "";
            }else {
              $coupon_session = "and cou.coupon_no not in ($coupon_session_list)";
            }
            if ($stip == 0) {
              $stip_list = "";
            }else {
              $stip_list = "coucl.COUPON_USE_STIP <= $stip AND";
            }
              $stmt = $this->conn->prepare("SELECT cou.COUPON_NO,cou.COUPON_CLASS_CD,cou.COUPON_USE_YN,cou.COUPON_REG_DATE,
                cou.COUPON_DEADLINE_TM,DATE_ADD(cou.COUPON_REG_DATE, INTERVAL cou.COUPON_DEADLINE_TM day),
                coucl.COUPON_DISCOUNT_RATE,cou.COUPON_NO,coucl.COUPON_DISCOUNT_PRICE,
                coucl.COUPON_USE_STIP,coucl.COUPON_CLASS_NAME,coucl.COUPON_CONT,coucl.COUPON_BENEFIT,coucl.COUPON_IMG,
                coucl.coupon_start_tm,coucl.coupon_end_tm,
                (SELECT count(seller_id) from TB_COUPON_BY_SELLER where cust_id = cou.CUST_ID and COUPON_NO = cou.COUPON_NO) as useall,
                (SELECT GROUP_CONCAT(seller_id) from TB_COUPON_BY_SELLER where cust_id = cou.CUST_ID and COUPON_NO = cou.COUPON_NO) as useCou
                FROM TB_COUPON cou join TB_COUPON_CLASS_CD coucl on cou.COUPON_CLASS_CD = coucl.COUPON_CLASS_CD
                WHERE
                (   /*조건시작*/

                        ((cou.COUPON_DEADLINE_TM > 0) AND (DATE_ADD(cou.COUPON_REG_DATE, INTERVAL cou.COUPON_DEADLINE_TM DAY) > now()))
                        OR
                       ((cou.COUPON_DEADLINE_TM = 0) AND (                   	(
                           (('0000-00-00' = coucl.coupon_end_tm) AND (coucl.coupon_end_tm < now()))
                           or
                           ((coucl.coupon_end_tm > now()) AND (coucl.coupon_end_tm > now()))
                       )))
                  )  /*조건마무리*/
                AND
                -- coucl.coupon_end_tm > now() AND
                coucl.COUPON_ACTIV_YN = 1 AND
                $stip_list
                cou.COUPON_USE_YN = 0 and cou.cust_id = ?
                $having
                $coupon_session
                order by cou.coupon_no,coucl.COUPON_DISCOUNT_PRICE desc");
              $stmt->bind_param("s", $cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function selectUserCouponList($cust_id,$coupon_date_where,$order_by)
          {
            if ($coupon_date_where == "") {
              $coupon_date_where = "";
              $COUPON_USE_YN = "";
              $COUPON_USE_YN = "AND cou.COUPON_USE_YN = 1 ";
              $COUPON_HAVING = "group by cou.coupon_no,his.seller_id HAVING RIGHT(GROUP_CONCAT(his.coupon_use_yn),1) = \"1\"";
            }else {
              $COUPON_HAVING = "group by cou.coupon_no";
              if ($coupon_date_where == "<") {
                $COUPON_USE_YN = "";
              }else {
                $COUPON_USE_YN = "AND cou.COUPON_USE_YN = 0 ";
              }
              // $coupon_date_where = "AND coucl.coupon_end_tm $coupon_date_where now()";
              $coupon_date_where = "
              AND
              (   /*조건시작*/

                    	((cou.COUPON_DEADLINE_TM > 0) AND (DATE_ADD(cou.COUPON_REG_DATE, INTERVAL cou.COUPON_DEADLINE_TM DAY) > now()))
                    	OR
                     ((cou.COUPON_DEADLINE_TM = 0) AND (                   	(
                         (('0000-00-00' = coucl.coupon_end_tm) AND (coucl.coupon_end_tm < now()))
                         or
                         ((coucl.coupon_end_tm > now()) AND (coucl.coupon_end_tm > now()))
                     )))
                )  /*조건마무리*/
              ";
            }

            switch ($order_by) {
              case '0'://최신순
              $order_by_set = "order by coucl.COUPON_DISCOUNT_PRICE desc";

                break;
              case '1'://금액순
              $order_by_set = "order by cou.COUPON_REG_DATE desc";
                break;
              default:
                $order_by_set = "order by coucl.COUPON_DISCOUNT_PRICE desc";
                break;
            }
              $stmt = $this->conn->prepare("SELECT cou.COUPON_NO,cou.COUPON_CLASS_CD,cou.COUPON_USE_YN,cou.COUPON_REG_DATE,
                cou.COUPON_DEADLINE_TM,DATE_ADD(cou.COUPON_REG_DATE, INTERVAL cou.COUPON_DEADLINE_TM day) as DTm,coucl.COUPON_DISCOUNT_RATE,cou.COUPON_NO,coucl.COUPON_DISCOUNT_PRICE,
                coucl.COUPON_USE_STIP,coucl.COUPON_CLASS_NAME,coucl.COUPON_CONT,coucl.COUPON_BENEFIT,coucl.COUPON_IMG,
                -- SUBSTRING_INDEX(group_concat(his.order_no),-1)
                max(his.order_no),coucl.coupon_start_tm,coucl.coupon_end_tm
                -- group_concat(his.coupon_use_yn)
                FROM TB_COUPON cou join TB_COUPON_CLASS_CD coucl on cou.COUPON_CLASS_CD = coucl.COUPON_CLASS_CD
                left join TB_COUPON_HIS his on cou.coupon_no = his.coupon_no
                WHERE
                cou.cust_id = ?
                $COUPON_USE_YN
                $coupon_date_where
                $COUPON_HAVING
                $order_by_set");
              $stmt->bind_param("s", $cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function deletecoponnone($cust_id,$seller_id){
            $stmt = $this->conn->prepare("DELETE from TB_CART WHERE cust_id=? and seller_id=?");
            $stmt->bind_param("ss",$cust_id,$seller_id);
            if ($stmt->execute()) {
                return DELETE_COMPLETED;
            } else {
                return DELETE_FAILED;
            }
          }

          public function insertCoponCart($coupon_no,$cust_id, $prod_cd, $prod_count,$seller_id)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
              $stmt = $this->conn->prepare("INSERT into TB_CART (coupon_no,cust_id, prod_cd, prod_count,seller_id) values(?,?, ?, ?, ?)");
              $stmt->bind_param("sssds", $coupon_no,$cust_id, $prod_cd, $prod_count,$seller_id);
              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }

          }

          public function selectuserCoupon($cust_id)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            $stmt = $this->conn->prepare("SELECT COUPON_NO,COUPON_CLASS_CD,COUPON_USE_YN,COUPON_REG_DATE,CUST_ID FROM TB_COUPON WHERE CUST_ID = ?
            and coupon_use_yn = 0");
            $stmt->bind_param("s", $cust_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectOrderCoupon($yn,$cust_id,$order_no,$seller_id)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            if (empty($seller_id)) {
              $seller_where = "";
            }else {
              $seller_where = "and cd.seller_id = \"$seller_id\"";
            }
            $stmt = $this->conn->prepare("SELECT cou.COUPON_NO, cou.COUPON_CLASS_CD, cou.COUPON_USE_YN, cou.COUPON_REG_DATE, cou.CUST_ID
          ,cd.COUPON_HIS_NO, cd.COUPON_NO, cd.ORDER_NO, cd.COUPON_USE_YN
          ,cd.COUPON_HIS_DATE, cd.COUPON_DISCOUNT_PRICE,sel.SELLER_NAME,cd.seller_id
          FROM TB_COUPON cou join TB_COUPON_HIS cd on cou.COUPON_NO = cd.COUPON_NO
          join TB_SELLER sel on cd.SELLER_ID =  sel.SELLER_ID
          WHERE cd.coupon_use_yn = ? and cou.cust_id = ? and cd.ORDER_NO = ?
          $seller_where");
            $stmt->bind_param("isi",$yn,$cust_id,$order_no);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectOrderCoupon_group($yn,$cust_id,$order_no,$ArrayTest)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            // if (empty($seller_id)) {
            //   $seller_where = "";
            // }else {
            //   $seller_where = "and cd.seller_id = \"$seller_id\"";
            // }
            $seller_id = $ArrayTest[0];
            $orderTM = $ArrayTest[1];
            $wtid = $ArrayTest[2];
            // echo "WTID : " . $wtid;
            if (isset($seller_id) && isset($orderTM)) {
              $joinItem = "(SELECT * FROM TB_COUPON_HIS as hisSub
              join (SELECT order_no,seller_id,ORDER_DEADLINE_TM,sum(order_pay*PROD_ORDER_CNT),
              sum(order_costpr*PROD_ORDER_CNT),sum(order_sel_costpr*PROD_ORDER_CNT),sum(coupon_price)
              FROM TB_ORDER_ITEM group by order_no,seller_id,ORDER_DEADLINE_TM) as itemSub
              on hisSub.order_no = itemSub.order_no
              and hisSub.seller_id = itemSub.seller_id
              and '$seller_id' = itemSub.seller_id
              and '$orderTM' = itemSub.ORDER_DEADLINE_TM )";
              // $joinItem = "(SELECT * FROM TB_COUPON_HIS as hisSub)";
              $joinItem = "(SELECT * FROM TB_COUPON_HIS WHERE
          (order_no REGEXP(SELECT REPLACE(Group_concat(order_no), ',','|') AS ORDER_NO
          FROM TB_ORDER WHERE
          (CASE WHEN '$wtid'=''
          THEN ORDER_NO = '$order_no'
          ELSE wtid = (SELECT NULLIF(wtid,'')
          FROM TB_ORDER WHERE ORDER_NO = '$order_no')  END))))";
            }else {
              // $joinItem = "TB_COUPON_HIS";
              $joinItem = "(SELECT * FROM TB_COUPON_HIS WHERE
          (order_no REGEXP(SELECT REPLACE(Group_concat(order_no), ',','|') AS ORDER_NO
          FROM TB_ORDER WHERE wtid
          (CASE WHEN '$wtid'=''
          THEN ORDER_NO = '$order_no'
          ELSE wtid = (SELECT NULLIF(wtid,'')
          FROM TB_ORDER WHERE ORDER_NO = '$order_no')  END))))";
            }
            $stmt = $this->conn->prepare("SELECT cou.COUPON_NO, cou.COUPON_CLASS_CD, cou.COUPON_USE_YN, cou.COUPON_REG_DATE, cou.CUST_ID
          ,cd.COUPON_HIS_NO, cd.COUPON_NO, cd.ORDER_NO, cd.COUPON_USE_YN
          ,cd.COUPON_HIS_DATE, cd.COUPON_DISCOUNT_PRICE,sel.SELLER_NAME,cd.seller_id
          ,cl_cd.coupon_class_name,cl_cd.COUPON_CONT,cl_cd.coupon_benefit,cl_cd.coupon_use_stip
          ,cl_cd.COUPON_START_TM,cl_cd.COUPON_END_TM,cl_cd.COUPON_IMG
          FROM TB_COUPON cou join $joinItem cd
          on cou.COUPON_NO = cd.COUPON_NO
          join TB_SELLER sel on cd.SELLER_ID =  sel.SELLER_ID
          join TB_COUPON_CLASS_CD cl_cd on cou.coupon_class_cd = cl_cd.coupon_class_cd
          WHERE cou.cust_id = ?
          GROUP by cd.COUPON_NO,cd.SELLER_ID
          HAVING GROUP_CONCAT(cd.coupon_use_yn order by cd.coupon_use_yn desc) = ?
          $seller_where");
            // $stmt->bind_param("sis",$cust_id,$order_no,$yn);
            $stmt->bind_param("ss",$cust_id,$yn);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectUseuserCoupon($cust_id,$coupon_no,$coupon_use_yn)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            $stmt = $this->conn->prepare("SELECT cou.COUPON_NO, cou.COUPON_CLASS_CD, cou.COUPON_USE_YN, cou.COUPON_REG_DATE, cou.CUST_ID
          ,cd.COUPON_CLASS_CD, cd.COUPON_DISCOUNT_RATE, cd.COUPON_DISCOUNT_PRICE, cd.COUPON_CLASS_NAME
          ,cd.COUPON_USE_STIP, cd.COUPON_CONT, cd.COUPON_BENEFIT, cd.COUPON_ACTIV_YN
          ,cd.COUPON_START_TM, cd.COUPON_END_TM
          FROM TB_COUPON cou join TB_COUPON_CLASS_CD cd on cou.COUPON_CLASS_CD = cd.COUPON_CLASS_CD
          WHERE cou.coupon_use_yn = $coupon_use_yn and cou.cust_id=? and cou.coupon_no = ?");
            $stmt->bind_param("ss",$cust_id,$coupon_no);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function insertCouponHis($coupon_no,$order_no,$seller_id,$coupon_discount_price,$coupon_use_yn)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            if ($coupon_use_yn == 0) {
              $stmt = $this->conn->prepare("INSERT INTO TB_COUPON_HIS(COUPON_NO, ORDER_NO,SELLER_ID, COUPON_HIS_DATE, COUPON_DISCOUNT_PRICE,coupon_use_yn)
              VALUES (?,?,?,now(),?,?)");
              $stmt->bind_param("sisii",$coupon_no,$order_no,$seller_id,$coupon_discount_price,$coupon_use_yn);
            }else {
              $stmt = $this->conn->prepare("INSERT INTO TB_COUPON_HIS(COUPON_NO, ORDER_NO,SELLER_ID, COUPON_HIS_DATE, COUPON_DISCOUNT_PRICE)
              VALUES (?,?,?,now(),?)");
              $stmt->bind_param("sisi",$coupon_no,$order_no,$seller_id,$coupon_discount_price);
            }
              if ($stmt->execute()) {
                if ($coupon_use_yn == 0) {
                  $stmtup = $this->conn->prepare("UPDATE TB_COUPON SET COUPON_USE_YN=0 where coupon_no = ?");
                }else {
                  $stmtup = $this->conn->prepare("UPDATE TB_COUPON SET COUPON_USE_YN=1 where coupon_no = ?");
                }
                $stmtup->bind_param("s",$coupon_no);
                if ($stmtup->execute()) {
                    return INSERT_COMPLETED;
                } else {
                    return INSERT_FAILED;
                }
                  // return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }

          }

          public function update_coupon_admin($coupon_class_cd,$coupon_class_name,$coupon_discount_price,$coupon_use_stip,$coupon_cont,$coupon_benefit,$coupon_start_tm,$coupon_end_tm,$coupon_img,$COUPON_TYPE_CD)
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("UPDATE TB_COUPON_CLASS_CD SET COUPON_DISCOUNT_PRICE=?,COUPON_CLASS_NAME=?,COUPON_USE_STIP=?,
               COUPON_CONT=?,COUPON_BENEFIT=?,COUPON_START_TM=?,COUPON_END_TM=?,COUPON_IMG=?,COUPON_TYPE_CD=? WHERE COUPON_CLASS_CD=?");
               //COUPON_ACTIV_YN=?,
      //VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
                $stmt->bind_param("isisssssss",$coupon_discount_price,$coupon_class_name,$coupon_use_stip,$coupon_cont,$coupon_benefit,$coupon_start_tm,$coupon_end_tm,$coupon_img,$COUPON_TYPE_CD,$coupon_class_cd);

              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }

          public function insert_coupon_admin($coupon_class_cd,$coupon_class_name,$coupon_discount_price,$coupon_use_stip,$coupon_cont,$coupon_benefit,$coupon_start_tm,$coupon_end_tm,$coupon_img,$COUPON_TYPE_CD)
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("INSERT INTO TB_COUPON_CLASS_CD(COUPON_REG_DATE, COUPON_CLASS_CD, COUPON_DISCOUNT_PRICE, COUPON_CLASS_NAME, COUPON_USE_STIP, COUPON_CONT, COUPON_BENEFIT,COUPON_START_TM, COUPON_END_TM, COUPON_IMG,COUPON_TYPE_CD)
             VALUES (now(5),?,?,?,?,?,?,?,?,?,?)");
               //COUPON_ACTIV_YN=?,
      //VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
                $stmt->bind_param("sisissssss",$coupon_class_cd,$coupon_discount_price,$coupon_class_name,$coupon_use_stip,$coupon_cont,$coupon_benefit,$coupon_start_tm,$coupon_end_tm,$coupon_img,$COUPON_TYPE_CD);

              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }
          }

          public function selectCouponLastClassCd()
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            $stmt = $this->conn->prepare("SELECT COUPON_CLASS_CD from TB_COUPON_CLASS_CD
              where COUPON_REG_DATE = (select max(COUPON_REG_DATE) from TB_COUPON_CLASS_CD)
            group by   COUPON_REG_DATE");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectcouponmod($numberm,$order_no,$seller_id)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            $stmt = $this->conn->prepare("SELECT
            order_item_no, order_no, seller_id, item.prod_cd, prod_order_cnt, order_cond_cd,
            order_pay, order_costpr, order_deadline_tm, order_sel_costpr,
            @ROWNUM:=@ROWNUM+1,
            @MOD:=$numberm-(select sum(mod_sum ) from (select round(
            (sum(order_pay*prod_order_cnt)   /   (select sum(order_pay*prod_order_cnt) from TB_ORDER_ITEM where order_no = $order_no and seller_id = '$seller_id'))
            *$numberm,0) as mod_sum from TB_ORDER_ITEM
            WHERE ORDER_NO = $order_no and seller_id ='$seller_id'
            group by ORDER_ITEM_NO) as mod_table)
            /*
            /*
            round(
            (sum(order_pay*prod_order_cnt)   /   (select sum(order_pay*prod_order_cnt) from TB_ORDER_ITEM where order_no = $order_no and seller_id = '$seller_id'))
            *$numberm,0),*/,
            if(@ROWNUM=1,
            round(
            (sum(order_pay*prod_order_cnt)   /   (select sum(order_pay*prod_order_cnt) from TB_ORDER_ITEM where order_no = $order_no and seller_id = '$seller_id'))
            *$numberm,0)+@MOD,
            /*+mod($numberm,(select count(order_item_no) from TB_ORDER_ITEM WHERE ORDER_NO = $order_no and seller_id ='$seller_id'))*/
            round(
            (sum(order_pay*prod_order_cnt)   /   (select sum(order_pay*prod_order_cnt) from TB_ORDER_ITEM where order_no = $order_no and seller_id = '$seller_id'))
            *$numberm,0)) as 비율,prd.taxfree_yn
            FROM TB_ORDER_ITEM item
            left join (SELECT prod_cd,taxfree_yn from TB_PROD) prd
            on item.prod_cd = prd.prod_cd,(SELECT @ROWNUM:=0) 변수,(SELECT @MOD:=0) 나머지
            WHERE ORDER_NO = $order_no and seller_id ='$seller_id'
            group by order_item_no
            ORDER BY ORDER_ITEM_NO  DESC");
            // $stmt->bind_param("iii",$number,$number,$number);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function select_invoice_order_list($date)
          { //echo "$cust_id, $prod_cd, $prod_count,$seller_id";
            $stmt = $this->conn->prepare("SELECT ord.ORDER_DATE,ord.CUST_ID,Group_concat(his.coupon_use_yn ORDER BY his.coupon_use_yn DESC) as 쿠폰사용
            ,his.COUPON_DISCOUNT_PRICE,his.ORDER_NO,his.SELLER_ID FROM TB_COUPON_HIS his
            left join TB_ORDER ord on his.ORDER_NO = ord.ORDER_NO
            where ord.REG_DATE BETWEEN left(LAST_DAY('$date' - interval 1 month),10) + interval 1 day and left(LAST_DAY('$date'),10)
            GROUP by his.ORDER_NO,his.SELLER_ID
            HAVING 쿠폰사용 = '1'");
            $stmt->bind_param("s",$date);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function updatecouponmod($order_item_no_cou,$o12_cou)
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("UPDATE TB_ORDER_ITEM SET coupon_price= ? WHERE ORDER_ITEM_NO = ?");
               //COUPON_ACTIV_YN=?,
      //VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
                $stmt->bind_param("ii",$o12_cou,$order_item_no_cou);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }

          public function selectDashboardUserClass($MONTH,$cust_id)
          {
            // echo "검색성공";

            $str = "SELECT SUBSTR(item.prod_cd,1,1) as 카테고리,sum(round(item.order_pay*item.PROD_ORDER_CNT)-item.coupon_price) as 매출,
            DATE_FORMAT(ord.ORDER_DATE,'%Y-%m') as 주문날짜,YEAR (ord.ORDER_DATE) as 년도,MONTH(ord.ORDER_DATE) as 월,count(item.prod_cd) as 주문상품수
            FROM TB_ORDER_ITEM item left join TB_ORDER ord on item.ORDER_NO = ord.ORDER_NO";

            $where = "WHERE DATE_FORMAT(ord.ORDER_DATE,'%Y-%m') BETWEEN DATE_FORMAT(SUBDATE(now(),INTERVAL $MONTH MONTH),'%Y-%m')
            and DATE_FORMAT(now(),'%Y-%m') and ord.cust_id = '$cust_id' and item.order_cond_cd = '03'";

            $group_by = "group by 카테고리,주문날짜";

            $order_by = "order by 주문날짜,카테고리";


            $stmt = $this->conn->prepare("$str $where $group_by $order_by");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectDashboardUser($cust_id,$taxfree,$MONTH)
          {
            // echo "검색성공";

            $str = "SELECT ord.order_no 오더,item.SELLER_ID 셀러,sum(item.order_pay*item.PROD_ORDER_CNT) 거래액,
            sum(item.coupon_price),DATE_FORMAT(ord.ORDER_DATE,'%Y-%m') as 주문날짜,YEAR (ord.ORDER_DATE) as 년도,
            MONTH(ord.ORDER_DATE) as 월 FROM TB_ORDER_ITEM item
            join TB_ORDER ord on item.ORDER_NO = ord.ORDER_NO
            left join TB_PROD prd on item.PROD_CD = prd.PROD_CD";

            if ($cust_id == "") {
              $cust_id_where = "";
              // code...
            }else {
              $cust_id_where = "ord.CUST_ID = '$cust_id' and";
            }
            //
            if ($taxfree == "ALL") {
              $taxfree_where = "";
              // code...
            }else {
              $taxfree_where = "prd.TAXFREE_YN = '$taxfree' and";
            }
            //
            $where = "WHERE $cust_id_where item.order_cond_cd = '03' and $taxfree_where
            DATE_FORMAT(ord.ORDER_DATE,'%Y-%m') BETWEEN DATE_FORMAT(SUBDATE(now(),INTERVAL $MONTH MONTH),'%Y-%m')
            and DATE_FORMAT(now(),'%Y-%m')";
            //
            $group_by = "group by 오더,셀러";

            $order_by = "order by 오더";


            $stmt = $this->conn->prepare("$str $where $group_by $order_by");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }


          public function selectPaymentHistoryGroup($his_cd,$cust_id)
          {
            // echo "검색성공";
            if ($his_cd == "BW") {
              $pay_his_cd = "(his.PAYMENT_HIS_CD = 'BW' or his.PAYMENT_HIS_CD = 'SI' or his.PAYMENT_HIS_CD = 'BB' or his.PAYMENT_HIS_CD = 'CP' or his.PAYMENT_HIS_CD = 'VW')";
            }else if($his_cd == "BC") {
              $pay_his_cd = "(his.PAYMENT_HIS_CD = 'BC' or his.PAYMENT_HIS_CD = 'SC' or his.PAYMENT_HIS_CD = 'BD' or his.PAYMENT_HIS_CD = 'CC' or his.PAYMENT_HIS_CD = 'VC')";
            }
            $str = "SELECT sum(PAYMENT_PR),DATE_FORMAT(his.PAYMENT_DATE,'%Y-%m') as 주문날짜,his_cd.PAYMENT_HIS_NAME,GROUP_CONCAT(his.PAYMENT_HIS_CD) FROM TB_CUST_PAYMENT_HIS his
            join TB_PAYMENT_HIS_CD his_cd on his.PAYMENT_HIS_CD = his_cd.PAYMENT_HIS_CD
            join (SELECT his2.ORDER_NO,GROUP_CONCAT(his2.PAYMENT_HIS_CD) cd_concat from (SELECT * FROM TB_CUST_PAYMENT_HIS WHERE PAYMENT_HIS_CD != 'CP') his1
            join TB_CUST_PAYMENT_HIS his2 on his1.order_no = his2.order_no group by order_no  having cd_concat not like '%CP%') as his_sub on his.order_no = his_sub.order_no";
            //            HAVING 주문날짜 ="2020-02"
            $where = "WHERE his.CUST_ID = '$cust_id' and $pay_his_cd";

            $group_by = "group by his.cust_id,주문날짜";
            // $group_by = "group by his.cust_id,주문날짜,his.PAYMENT_HIS_CD";

            $order_by = "order by 주문날짜";


            $stmt = $this->conn->prepare("$str $where $group_by $order_by");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectAdmin()
          {
            // echo "검색성공";

            $str = "SELECT admin_id,admin_name from TB_ADMIN where admin_type = 'SALES'";

            $stmt = $this->conn->prepare("$str");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectAdminWhere($cust_id)
          {
            // echo "검색성공";

            $str = "SELECT admin.admin_id,admin.admin_name,admin.ADMIN_TEL_NO from TB_ADMIN admin join TB_CUST cust on admin.ADMIN_ID = cust.ADMIN_ID where cust.cust_id ='$cust_id'";
            $stmt = $this->conn->prepare("$str");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function selectCustAdmin($cust_id,$admin_id)
          {
            // echo "검색성공";

            $str = "SELECT cust.BUSINESS_NAME,admin.ADMIN_TEL_NO,cust.tel_no
            from TB_CUST cust left join TB_ADMIN admin
            on cust.ADMIN_ID = admin.ADMIN_ID where cust.CUST_ID = '$cust_id'";
            $stmt = $this->conn->prepare("$str");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function checkAdminId($admin_id)
          {
              $stmt = $this->conn->prepare("SELECT ADMIN_ID,ADMIN_NAME FROM TB_ADMIN WHERE ADMIN_ID=?");
              $stmt->bind_param("s",$admin_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return SELECT_COMPLETED;
              } else {
                  return SELECT_FAILED;
              }

          }

          //Function to create a new user
          public function createAdmin($admin_id,$pass, $admin_name,$tel_no,$admin_type)
          {
              if (!$this->isAdminExist($admin_id,$admin_name,$tel_no)) {
                // echo "$admin_id, $pass, $business_name, $owner_name, $addr_cont, $tel_no,$addr_cd,$INVITE_RECOMMENDER_CODE,$RECOMMENDER_TEL_NO,$benefit,$email";
                  $password = md5($pass);
                  $stmt = $this->conn->prepare("INSERT INTO TB_ADMIN(ADMIN_ID,PASSWORD,ADMIN_NAME,ADMIN_TEL_NO,ADMIN_TYPE,REG_DATE) VALUES (?,?,?,?,?,now())");
                  $stmt->bind_param("sssss",$admin_id,$password, $admin_name,$tel_no,$admin_type);
                  if ($stmt->execute()) {
                      return USER_CREATED;
                  } else {
                      return USER_NOT_CREATED;
                  }
              } else {
                  return USER_ALREADY_EXIST;
              }
          }

          private function isAdminExist($admin_id,$admin_name,$tel_no)
          {
              $stmt = $this->conn->prepare("SELECT ADMIN_ID FROM TB_ADMIN WHERE (ADMIN_ID = ? OR ADMIN_NAME = ? OR ADMIN_TEL_NO =?)");
              $stmt->bind_param("sss", $admin_id,$admin_name, $tel_no);
              $stmt->execute();
              $stmt->store_result();
              return $stmt->num_rows > 0;
          }

          public function click_history($cust_id,$param,$url)
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("INSERT INTO TB_CLICK_HIS(CLICK_CUST_ID, CLICK_ID, URL, REG_DATE) VALUES (?,?,?,now())");
               //COUPON_ACTIV_YN=?,
      //VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
                $stmt->bind_param("sss",$cust_id,$param,$url);

              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }
          }


          public function insert_push_info($NOTIFICATION_ID,$NOTIFICATION_CD,$CUST_ID,$USE_INFO)
          {

            if (!$this->isPUSHExist($NOTIFICATION_ID)) {//INSERT
              $stmt = $this->conn->prepare("INSERT INTO TB_NOTIFICATION(NOTIFICATION_ID,NOTIFICATION_CD, CUST_ID, USE_INFO, REG_DATE) VALUES (?,?,?,?,now())");
             $stmt->bind_param("ssss",$NOTIFICATION_ID,$NOTIFICATION_CD,$CUST_ID,$USE_INFO);
             if ($stmt->execute()) {
                 return INSERT_COMPLETED;
             } else {
                 return INSERT_FAILED;
             }
           } else {//UPDATE
              $stmt = $this->conn->prepare("UPDATE TB_NOTIFICATION SET CUST_ID=?,UPDATE_DATE=now() where NOTIFICATION_ID=?");
             $stmt->bind_param("ss",$CUST_ID,$NOTIFICATION_ID);
             if ($stmt->execute()) {
                 return INSERT_COMPLETED;
             } else {
                 return INSERT_FAILED;
             }
            }
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
            //()
            // private function isUserExist($cust_id, $business_name, $tel_no)
            // {
            //     $stmt = $this->conn->prepare("SELECT cust_id FROM TB_CUST WHERE cust_id = ? OR business_name = ? ");
            //     $stmt->bind_param("sss", $cust_id, $business_name, $tel_no);
            //     $stmt->execute();
            //     $stmt->store_result();
            //     return $stmt->num_rows > 0;
            // }
            //  $stmt = $this->conn->prepare("INSERT INTO TB_NOTIFICATION(NOTIFICATION_ID, CUST_ID, USE_INFO, REG_DATE) VALUES (?,?,?,now())");
            // $stmt->bind_param("sss",$NOTIFICATION_ID,$CUST_ID,$USE_INFO);
            //
            //   if ($stmt->execute()) {
            //       return INSERT_COMPLETED;
            //   } else {
            //       return INSERT_FAILED;
            //   }
          }
          private function isPUSHExist($NOTIFICATION_ID)
          {
              $stmt = $this->conn->prepare("SELECT NOTIFICATION_ID FROM TB_NOTIFICATION WHERE NOTIFICATION_ID = ?");
              $stmt->bind_param("s", $NOTIFICATION_ID);
              $stmt->execute();
              $stmt->store_result();
              return $stmt->num_rows > 0;
          }
          //신동진 딜랩 매칭여부 확인
          public function selectDeliverylabMetching($cust_id)
          {
              $stmt = $this->conn->prepare("SELECT count(SELLER_ID),(SELECT sum(item.PROD_ORDER_CNT) from TB_ORDER_ITEM item join TB_ORDER ord on item.order_no = ord.order_no where ord.cust_id = '$cust_id' and item.prod_cd = 'A0310046' and item.seller_id = 'deliverylab' and (item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03' or item.order_cond_cd = '04')) from TB_SELLER_BY_CUST WHERE cust_id=? and SELLER_ID = 'deliverylab'");
              $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
              // return $stmt->num_rows > 0;
          }

          //성수동 가입자 유통업체 이름
          public function selectDeliverylabSdg($sel)
          {
              $stmt = $this->conn->prepare("SELECT SELLER_NAME,IF(SELLER_CONT='',SELLER_NAME,IFNULL(SELLER_CONT,SELLER_NAME)) FROM TB_SELLER WHERE SELLER_ID=?");
              $stmt->bind_param("s",$sel);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
              // return $stmt->num_rows > 0;
          }
          //성수동 가입자 유통업체 이름
          public function selectDeliverylabSdgViewName($sel)
          {
              $stmt = $this->conn->prepare("SELECT SELLER_CONT FROM TB_SELLER WHERE SELLER_ID=?");
              $stmt->bind_param("s",$sel);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
              // return $stmt->num_rows > 0;
          }



          public function update_cart_memo($memo,$cust_id,$prod,$seller_id)
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("UPDATE TB_CART SET CART_MEMO=? WHERE CUST_ID=? and prod_cd=? and seller_id = ?");
               //COUPON_ACTIV_YN=?,
      //VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
                $stmt->bind_param("ssss",$memo,$cust_id,$prod,$seller_id);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }
          // public function update_push_info($NOTIFICATION_ID,$CUST_ID,$USE_INFO)
          // {
          //   // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
          //    $stmt = $this->conn->prepare("UPDATE TB_NOTIFICATION SET (NOTIFICATION_ID, CUST_ID, USE_INFO, REG_DATE) VALUES (?,?,?,now())");
          //   $stmt->bind_param("sss",$NOTIFICATION_ID,$CUST_ID,$USE_INFO);
          //
          //     if ($stmt->execute()) {
          //         return INSERT_COMPLETED;
          //     } else {
          //         return INSERT_FAILED;
          //     }
          // }

          public function select_grade_push($cust_id)
          {

              // $coupon_price = "sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)";
              //
              // $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
              //
              // $seller = "and item.SELLER_ID like '%$seller_id%'";
              //
              if (!isset($cust_id) || empty($cust_id)) {
                $cust = "cust.ACTIV_YN = 1 and gcd.GRADE_CLASS_CD != 'egg0'";
              }else {
                $cust = "cust.CUST_ID like '$cust_id' and cust.ACTIV_YN = 1 and gcd.GRADE_CLASS_CD != 'egg0'";
              }

              //
              // $admin_type_join = " join
              // (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
              // on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id')
              // cust on ord.cust_id = cust.cust_id";

              $stmt = $this->conn->prepare("SELECT notion.NOTIFICATION_ID,cust.CUST_ID,cust.BUSINESS_NAME,gcd.GRADE_CLASS_CD,gcd.GRADE_CLASS_NAME,cust.tel_no,
                notion.NOTIFICATION_CD,notion.USE_INFO
                FROM TB_CUST cust  left join TB_NOTIFICATION notion on  cust.CUST_ID = notion.CUST_ID
                join TB_GRADE_CLASS_CD gcd on cust.GRADE_CLASS_CD = gcd.GRADE_CLASS_CD
                WHERE $cust");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function select_all_push($cust_id)
          {
              // $coupon_price = "sum(item.order_pay * item.prod_order_cnt)-(coupon.COUPON_DISCOUNT_PRICE)";
              //
              // $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' and '$date2'";
              //
              // $seller = "and item.SELLER_ID like '%$seller_id%'";
              //
              if (!isset($cust_id) || empty($cust_id)) {
                $cust = "cust.ACTIV_YN = 1 and notion.NOTIFICATION_ID is not null";
              }else {
                $cust = "cust.CUST_ID like '$cust_id' and cust.ACTIV_YN = 1";
              }
              //
              // $admin_type_join = " join
              // (SELECT cust.* from TB_CUST cust join TB_ADMIN admin
              // on cust.admin_id = admin.admin_id where cust.admin_id = '$admin_id')
              // cust on ord.cust_id = cust.cust_id";
              $stmt = $this->conn->prepare("SELECT notion.NOTIFICATION_ID,cust.CUST_ID,cust.BUSINESS_NAME,cust.tel_no,
                notion.NOTIFICATION_CD,notion.USE_INFO
                FROM TB_CUST cust  left join TB_NOTIFICATION notion on  cust.CUST_ID = notion.CUST_ID
                left join (SELECT * from TB_CART group by cust_id) cart on cust.cust_id = cart.cust_id
                WHERE $cust and cart.cust_id is not null order by notion.NOTIFICATION_ID desc");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //PUSH history
          public function insert_all_push_history($admin_id)
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("INSERT INTO TB_NOTIFICATION_HIS(NOTIFICATION_DATE,ADMIN_ID) VALUES (now(),?)");
             $stmt->bind_param("s",$admin_id);
              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }
          }
          public function select_grade_All_update()
          {
            // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
             $stmt = $this->conn->prepare("UPDATE TB_CUST,
                   (SELECT cust.cust_id,
                           cust.business_name,
                           sel.seller_id,
                           Sum(item.orderpay)   AS sum_orderPay,
                           Count(item.order_no) AS sum_orderCnt,
                           Sum(item.couponpay)  AS sum_couponPay,
                           CASE
                             WHEN ( Sum(item.orderpay) - Sum(item.couponpay) ) >= 10000000
                                  AND Count(item.order_no) >= 20 THEN 'egg6'
                             WHEN ( Sum(item.orderpay) - Sum(item.couponpay) ) >= 5000000
                                  AND Count(item.order_no) >= 15 THEN 'egg5'
                             WHEN ( Sum(item.orderpay) - Sum(item.couponpay) ) >= 3000000
                                  AND Count(item.order_no) >= 10 THEN 'egg4'
                             WHEN ( Sum(item.orderpay) - Sum(item.couponpay) ) >= 2000000
                                  AND Count(item.order_no) >= 8 THEN 'egg3'
                             WHEN ( Sum(item.orderpay) - Sum(item.couponpay) ) >= 1000000
                                  AND Count(item.order_no) >= 5 THEN 'egg2'
                             WHEN ( Sum(item.orderpay) - Sum(item.couponpay) ) >= 500000
                                  AND Count(item.order_no) >= 3 THEN 'egg1'
                             ELSE 'egg0'
                           end                  AS hero_type
                    FROM   TB_CUST cust
                           LEFT JOIN TB_SELLER sel
                                  ON cust.cust_id = sel.seller_id
                           LEFT JOIN (SELECT o.cust_id,
                                             i.order_no,
                                             Sum(i.order_pay * i.prod_order_cnt) AS orderPay
                                             ,
            Date_format(o.order_date, '%Y-%m')  AS ord_date,
            Sum(i.coupon_price)                 AS couponPay
            FROM   TB_ORDER_ITEM i
            JOIN TB_ORDER o
              ON i.order_no = o.order_no
            WHERE  ( i.order_cond_cd = '01'
               OR i.order_cond_cd = '02'
               OR i.order_cond_cd = '03' )
            GROUP  BY i.order_no
            HAVING ord_date = Date_format(Date_add(Now(),INTERVAL -1 month),'%Y-%m')) item
            ON cust.cust_id = item.cust_id
            WHERE  sel.seller_id IS NULL
            AND cust.activ_yn = 1
            GROUP  BY cust.cust_id) AS a
            SET    TB_CUST.grade_class_cd = a.hero_type
            WHERE  TB_CUST.cust_id = a.cust_id");
               //COUPON_ACTIV_YN=?,
      //VALUES ("A02","f","테스트상품","상품내용2","3kg",10,"김박사",'0','02','국내산',now(),"조합상품코드")");
                // $stmt->bind_param("ssss",$memo,$cust_id,$prod,$seller_id);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }



        public function select_grade_All_select()
        {
            $stmt = $this->conn->prepare("SELECT cust.cust_id, cust.business_name,grd.COUPON_CLASS_CD,grd.GRADE_BENEFIT_AM,GROUP_CONCAT(DATE_FORMAT(cou_list.coupon_reg_date,'%Y-%m')) as concatDate
              ,cust.GRADE_CLASS_CD
              FROM   TB_CUST cust LEFT JOIN TB_SELLER sel ON cust.cust_id = sel.seller_id
              join TB_GRADE_CLASS_CD grd on cust.GRADE_CLASS_CD = grd.GRADE_CLASS_CD
              join TB_COUPON_CLASS_CD cou on cou.COUPON_CLASS_CD = grd.COUPON_CLASS_CD
              left join (SELECT * from TB_COUPON where coupon_reg_date like concat('%',DATE_FORMAT(now(),'%Y-%m'),'%')
	             and (COUPON_CLASS_CD = 'AAS' or
            		                COUPON_CLASS_CD = 'AAT' or
            		                COUPON_CLASS_CD = 'AAU' or
            		                COUPON_CLASS_CD = 'AAV' or
            		                COUPON_CLASS_CD = 'AAW' or
            		                COUPON_CLASS_CD = 'AAX')) cou_list on cust.CUST_ID = cou_list.CUST_ID
              WHERE  sel.seller_id IS NULL AND cust.activ_yn = 1 and cust.GRADE_CLASS_CD != 'egg0'
	             GROUP BY cust.cust_id having concatDate is null");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
        }
        //등급 history
        public function insertGradeHis($GRADE_CLASS_CD,$CUST_ID)
        {
          // echo "$prod_name,$prod_cont,$prod_wgt,$sale_unit,$fact_name,$taxfree_yn,$stn_cond_cd,$odt,$prod_cd,$detail_cd";
           $stmt = $this->conn->prepare("INSERT INTO TB_GRADE_HIS(GRADE_CLASS_CD,CUST_ID, REG_DATE) VALUES (?,?,now())");
           $stmt->bind_param("ss",$GRADE_CLASS_CD,$CUST_ID);
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
            } else {
                return INSERT_FAILED;
            }
        }

        public function selectNewMetching($cust_id)
        { //신규매칭쿠폰 등록여부확인 5천 2장/2만 1장에서 20.07.09일 1만 3장으로변경
            $stmt = $this->conn->prepare("SELECT coupon_no FROM TB_COUPON WHERE cust_id = '$cust_id'
              and (COUPON_CLASS_CD = 'AAZ' or COUPON_CLASS_CD ='ABA' or COUPON_CLASS_CD ='ABJ' or COUPON_CLASS_CD ='ABM')");
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
        }

          public function select_grade_order($cust_id,$month)
          {
            if ($month < 0) {
              $format_date  = "DATE_FORMAT(date_add(now(),INTERVAL -1 MONTH),'%Y-%m')";
            }else {
              $format_date  = "DATE_FORMAT(now(),'%Y-%m')";
            }

              $stmt = $this->conn->prepare("SELECT cust.BUSINESS_NAME,ord.ORDER_NO,
              DATE_FORMAT(ord.order_date,'%Y-%m') as ord_date,count(ord.ORDER_NO),sum(-(item.orderPay)),
              cust.grade_class_cd,gcd.grade_class_name,gcd.grade_accrue_pay
              FROM TB_CUST cust join TB_ORDER ord on cust.CUST_ID = ord.CUST_ID
              join (SELECT order_no,sum(order_pay*prod_order_cnt) as orderPay
              from TB_ORDER_ITEM where (ORDER_COND_CD = '01' or ORDER_COND_CD = '02' or ORDER_COND_CD = '03')
              group by order_no) item  on ord.ORDER_NO = item.ORDER_NO
              join TB_GRADE_CLASS_CD gcd on cust.grade_class_cd = gcd.grade_class_cd
              WHERE ord.cust_id = '$cust_id' group by ord_date HAVING ord_date = $format_date");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function select_grade_list()
          {
              $stmt = $this->conn->prepare("SELECT GRADE_CLASS_CD,GRADE_CLASS_NAME,
                GRADE_ACCRUE_CNT,GRADE_ACCRUE_PAY,GRADE_BENEFIT_AM,gre.COUPON_CLASS_CD,
                cou.COUPON_DISCOUNT_PRICE,cou.COUPON_BENEFIT
                FROM TB_GRADE_CLASS_CD gre join TB_COUPON_CLASS_CD cou
                on gre.COUPON_CLASS_CD = cou.COUPON_CLASS_CD
                order by GRADE_CLASS_CD desc");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          public function select_grade_cust($g_cd)
          {
                // $stmt = $this->conn->prepare("SELECT cust.GRADE_CLASS_CD,gcd.GRADE_CLASS_NAME,
                //   (SELECT GRADE_ACCRUE_CNT from TB_GRADE_CLASS_CD
                //    where GRADE_CLASS_CD = if(cust.GRADE_CLASS_CD='egg6','egg6',
                //      concat('egg',(SUBSTR(cust.GRADE_CLASS_CD,4,1)+1)))),
                //   (SELECT GRADE_ACCRUE_PAY from TB_GRADE_CLASS_CD
                //    where GRADE_CLASS_CD = if(cust.GRADE_CLASS_CD='egg6','egg6',
                //      concat('egg',(SUBSTR(cust.GRADE_CLASS_CD,4,1)+1)))),
                //   gcd.GRADE_BENEFIT_AM,
                //   cou.COUPON_DISCOUNT_PRICE,cou.COUPON_USE_STIP,cou.COUPON_BENEFIT
                //   from TB_CUST cust join TB_GRADE_CLASS_CD gcd on cust.GRADE_CLASS_CD = gcd.GRADE_CLASS_CD
                //   join TB_COUPON_CLASS_CD cou on gcd.COUPON_CLASS_CD = cou.COUPON_CLASS_CD
                //   where cust.CUST_ID = ?");
                $stmt = $this->conn->prepare("SELECT gcd.GRADE_CLASS_CD,gcd.GRADE_CLASS_NAME,
                  (SELECT GRADE_ACCRUE_CNT from TB_GRADE_CLASS_CD
                  where GRADE_CLASS_CD = if(gcd.GRADE_CLASS_CD='egg6','egg6',
                  concat('egg',(SUBSTR(gcd.GRADE_CLASS_CD,4,1)+1)))),
                  (SELECT GRADE_ACCRUE_PAY from TB_GRADE_CLASS_CD
                  where GRADE_CLASS_CD = if(gcd.GRADE_CLASS_CD='egg6','egg6',
                  concat('egg',(SUBSTR(gcd.GRADE_CLASS_CD,4,1)+1)))),
                  gcd.GRADE_BENEFIT_AM,
                  cou.COUPON_DISCOUNT_PRICE,cou.COUPON_USE_STIP,cou.COUPON_BENEFIT
                  FROM TB_GRADE_CLASS_CD gcd
                  join TB_COUPON_CLASS_CD cou on gcd.COUPON_CLASS_CD = cou.COUPON_CLASS_CD
                  WHERE gcd.GRADE_CLASS_CD = ?");

              $stmt->bind_param("s",$g_cd);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function select_user_selet_coupon($keyDeliv,$yn,$coupon_code,$custName,$date1,$date2,$admin_type,$admin_id,$s_point = null,$list = null)
          {
            if (isset($s_point) && isset($list)) {
              $limit = "limit $s_point,$list";
            }else {
              $limit = "";
            }
            if ($yn == "Y") {
              $whereYN = "and history.coupon_no is not null";
            }else if ($yn == "N") {
              $whereYN = "and history.coupon_no is null";
            }else {
              $whereYN = "";
            }
           // echo "$admin_type,$admin_id";
           if (!empty($keyDeliv) && isset($keyDeliv)) {
            if ($keyDeliv == "basic") {
              // $sdg_get_where = " and (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
              $deliv = "and cust.DELIV_POSITION is null";
            }else if($keyDeliv == "sdg"){
              // $sdg_get_where = " and cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
              $deliv = "and cust.DELIV_POSITION is not null";
            }else if(strpos($keyDeliv,"|") !== false) {
              $deliv = " and cust.DELIV_POSITION REGEXP ('$keyDeliv')";
            }else {
              $deliv = "and cust.DELIV_POSITION like '$keyDeliv' ";
            }
           }else {
             $deliv = "";
           }

           //

            if ($admin_type == "SALES") {
              $admin_type_where = "and cust.admin_id = '$admin_id'";
            }
            $where_date = "where date_format(cou.COUPON_REG_DATE,'%Y-%m-%d') between '$date1' and '$date2'";

            if ($coupon_code == "" || empty($coupon_code) || !isset($coupon_code)) {
              $where_code ="";
            }else {
              $where_code ="and cocd.COUPON_CLASS_CD like '$coupon_code' and  cust.BUSINESS_NAME like concat('%','$custName','%')";
            }

            if ($custName == "" || empty($custName) || !isset($custName)) {
              // $where_name ="and cust.BUSINESS_NAME like concat('%','$custName','%')";
            }else {
              $where_name ="and cust.cust_id like concat('%','$custName','%')";
            }



            $use_yn = "and history.order_date is not null";
            $use_yn = "";
            //history.coupon_his_date



              $stmt = $this->conn->prepare("SELECT cocd.COUPON_CONT,cou.COUPON_NO,cust.CUST_ID,cust.BUSINESS_NAME,cocd.COUPON_CLASS_NAME,cocd.COUPON_CLASS_CD,
    cou.COUPON_DEADLINE_TM,cocd.COUPON_DISCOUNT_PRICE,history.coupon_no,history.order_date,cocd.COUPON_END_TM,cou.COUPON_REG_DATE,cust.DELIV_POSITION

    FROM TB_COUPON cou join TB_CUST cust on cou.CUST_ID = cust.CUST_ID

    join TB_COUPON_CLASS_CD cocd on cou.COUPON_CLASS_CD = cocd.COUPON_CLASS_CD
    left join
    (
    SELECT cou_his.coupon_no,item.*,cou_his.coupon_his_date FROM TB_COUPON_HIS cou_his
    join
    (SELECT it.*,od.order_date from
    TB_ORDER_ITEM it join TB_ORDER od on it.order_no = od.order_no
    where (it.order_cond_cd = '01' or it.order_cond_cd = '02' or it.order_cond_cd = '03' )
     group by it.order_no ) item

    on cou_his.ORDER_NO = item.order_no
    ORDER BY item.order_cond_cd  ASC) history
    on cou.coupon_no = history.coupon_no
    $where_date $where_code $whereYN $deliv $where_name $use_yn $admin_type_where
    order by cou.COUPON_REG_DATE desc $limit");
    // $stmt->bind_param("s",$coupon_code);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function select_attendance_list($cust_id)
          {
              $stmt = $this->conn->prepare("SELECT CUST_ID,REG_DATE,ACTIV_YN FROM TB_ATTENDANCE_HIS
                WHERE CUST_ID ='$cust_id' and reg_date like concat('%',DATE_FORMAT(now(),'%Y-%m'),'%')");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          private function isAttCheckExist($cust_id)
          {
              $stmt = $this->conn->prepare("SELECT concat(YEAR(REG_DATE),'-',
              if(MONTH(REG_DATE)<10,concat('0',MONTH(REG_DATE)),MONTH(REG_DATE)),'-',
              if(DAY(REG_DATE)<10,concat('0',DAY(REG_DATE)),DAY(REG_DATE)))
              FROM TB_ATTENDANCE_HIS WHERE cust_id = ? and activ_yn = 1 order by ATTENDANCE_NO desc limit 1");
              $stmt->bind_param("s", $cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          public function delete_last_att_his($cust_id)
          {
            $stmt = $this->conn->prepare("DELETE from TB_ATTENDANCE_HIS
            where cust_id = ?  and activ_yn = 1 order by ATTENDANCE_NO desc limit 1");
            $stmt->bind_param("s", $cust_id);
            if ($stmt->execute()) {
                return DELETE_COMPLETED;
            } else {
                return DELETE_FAILED;
            }
          }

          public function insert_att_his($cust_id)
          {//출석체크
              $select = $this->isAttCheckExist($cust_id);
              if ($select == SELECT_FAILED) {
                $stmt = $this->conn->prepare("INSERT INTO TB_ATTENDANCE_HIS(CUST_ID,REG_DATE) VALUES (?,now())");
                $stmt->bind_param("s",$cust_id);

                  if ($stmt->execute()) {
                      return INSERT_COMPLETED;
                  } else {
                      return INSERT_FAILED;
                  }
              }else {
                $select->bind_result($date);
                while ($select->fetch()) {
                  if ($date == date("Y-m-d", time())) {
                    return INSERT_FAILED;
                  }else {
                    $stmt = $this->conn->prepare("INSERT INTO TB_ATTENDANCE_HIS(CUST_ID,REG_DATE) VALUES (?,now())");
                    $stmt->bind_param("s",$cust_id);

                      if ($stmt->execute()) {
                          return INSERT_COMPLETED;
                      } else {
                          return INSERT_FAILED;
                      }
                  }
                }
              }
          }

          public function select_update_grade($month,$cust_id,$yn)
          {
            //카드주문금액
            $ifnull_card_pay = "Ifnull((Sum(item.orderpay)-Sum(item.couponpay)),0)";
            //입금기준 예치금주문금액
            $ifnull_ye_pay = "if(
            Ifnull(-( bd_pay.bd_pr ), 0) < Ifnull((Sum(dipo_item.orderpay)-Sum(dipo_item.couponpay)), 0),
              Ifnull(-( bd_pay.bd_pr ), 0),
              Ifnull((Sum(dipo_item.orderpay)-Sum(dipo_item.couponpay)), 0)
            )";
            //카드주문건
            $ifnull_card_count = "Ifnull(item.count_order,0)";
            //입금기준 예치금주문건
            $ifnull_ye_count = "Ifnull(dipo_item.count_order,0)";

            // 그룹 아이템 테이블
            $group_item_table =  "SELECT order_no,
                    Sum(order_pay * prod_order_cnt) pay_cnt,
                    Sum(coupon_price)               cou_prc,
                    order_cond_cd
             FROM   TB_ORDER_ITEM
             WHERE  ( order_cond_cd = '01'
                       OR order_cond_cd = '02'
                       OR order_cond_cd = '03' )
             GROUP  BY order_no";

             if ($month > 0) {
               //// 이번달날짜
               $Date_format_Now =  "Date_format(Now(),'%Y-%m')";
             }else {
               //// 전달날짜
               $Date_format_Now =  "Date_format(Date_add(Now(),INTERVAL -1 month),'%Y-%m')";
             }
            // $yn = "UPDATE";
            if ($yn == "UPDATE") {
              $yn_one = "UPDATE TB_CUST,(";
              $yn_tow = ") AS a
              SET    TB_CUST.grade_class_cd = a.hero_type
              WHERE  TB_CUST.cust_id = a.cust_id";
            }else {
              $yn_one = "";
              $yn_tow = "";
            }
            $stmt = $this->conn->prepare("$yn_one SELECT cust.cust_id,
       cust.business_name,
       sel.seller_id,
       /*Ifnull(Sum(item.orderpay), 0)       AS 카드결제금액,
       item.count_order                    AS 카드결제건수,
       Ifnull(Sum(item.couponpay), 0)      AS 카드결제쿠폰사용금액,
       Ifnull(Sum(dipo_item.orderpay), 0)  AS 예치금결제금액,
       dipo_item.count_order               AS 예치금결제건수,
       Ifnull(Sum(dipo_item.couponpay), 0) AS 예치금결제쿠폰사용금액,*/
       $ifnull_card_pay      AS 카드결제금액,
       $ifnull_card_count AS 카드결제건수,
       $ifnull_ye_pay AS 예치금결제금액,
       $ifnull_ye_count AS 예치금결제건수,/*입금액이 예치금결제보다 큰가 작은가*/
       CASE
         WHEN ($ifnull_card_pay+$ifnull_ye_pay) >= 10000000
              AND ($ifnull_card_count+$ifnull_ye_count) >= 20 THEN 'egg6'
         WHEN ($ifnull_card_pay+$ifnull_ye_pay) >= 5000000
              AND ($ifnull_card_count+$ifnull_ye_count) >= 15 THEN 'egg5'
         WHEN ($ifnull_card_pay+$ifnull_ye_pay) >= 3000000
              AND ($ifnull_card_count+$ifnull_ye_count) >= 10 THEN 'egg4'
         WHEN ($ifnull_card_pay+$ifnull_ye_pay) >= 2000000
              AND ($ifnull_card_count+$ifnull_ye_count) >= 8 THEN 'egg3'
         WHEN ($ifnull_card_pay+$ifnull_ye_pay) >= 1000000
              AND ($ifnull_card_count+$ifnull_ye_count) >= 5 THEN 'egg2'
         WHEN ($ifnull_card_pay+$ifnull_ye_pay) >= 500000
              AND ($ifnull_card_count+$ifnull_ye_count) >= 3 THEN 'egg1'
         ELSE 'egg0'
       END                                 AS hero_type,
       cust_pay.deposit_bln
FROM   TB_CUST cust
       LEFT JOIN TB_SELLER sel
              ON cust.cust_id = sel.seller_id
       LEFT JOIN /*카드결제시작*/(SELECT cust_id,
                         order_no,
                         Sum(orderpay)            AS orderPay,
                         ord_date,
                         Sum(couponpay)           AS couponPay,
                         Count(sub_item.order_no) AS count_order
                  FROM   /*카드 서브 아이템시작*/(SELECT cust_id,
                                 i.order_no,
                                 Sum(i.pay_cnt)                     AS orderPay,
                                 o.oDate AS ord_date,
                                 Sum(i.cou_prc)                     AS couponPay
                                 ,
Count(o.order_no)                  AS count_order
FROM   ($group_item_table) i
JOIN (SELECT *,Date_format(order_date,'%Y-%m') as oDate
      FROM   TB_ORDER
      WHERE  wtid != ''
group by order_no
having oDate  = $Date_format_Now) o
  ON i.order_no = o.order_no
GROUP  BY i.order_no
/*HAVING ord_date = Date_format(Now(), '%Y-%m')*/) AS sub_item /*카드 서브 아이템끝*/
GROUP  BY cust_id) item/*카드결제끝*/
ON cust.cust_id = item.cust_id
LEFT JOIN /*예치금결제시작*/(SELECT cust_id,
order_no,
Sum(orderpay)                 AS orderPay,
ord_date,
Sum(couponpay)                AS couponPay,
Count(sub_dipo_item.order_no) AS count_order
FROM   (/*예치금 서브 아이템시작*/SELECT o.cust_id,
i.order_no,
Sum(i.pay_cnt)                     AS orderPay,
o.oDate AS ord_date,
Sum(i.cou_prc)                     AS couponPay
FROM   ($group_item_table) i
JOIN (SELECT *,Date_format(order_date,'%Y-%m') as oDate
      FROM   TB_ORDER
      WHERE  wtid IS NULL
              OR wtid = ''
              group by order_no
              having oDate  = $Date_format_Now) o
  ON i.order_no = o.order_no
GROUP  BY i.order_no
/*HAVING ord_date = Date_format(Now(), '%Y-%m')*/) AS sub_dipo_item /*예치금 서브 아이템끝*/
GROUP  BY cust_id) dipo_item /*예치금결제끝*/
ON cust.cust_id = dipo_item.cust_id
LEFT JOIN (SELECT bd_his.cust_id,
( bd_his.bd_pr + Ifnull(bb_his.bd_pr, 0) ) AS bd_pr
FROM   (SELECT cust_id,
Sum(payment_pr) bd_pr
FROM   TB_CUST_PAYMENT_HIS
WHERE  payment_date LIKE Concat('%', $Date_format_Now, '%')
AND payment_his_cd = 'BD'
GROUP  BY cust_id) AS bd_his
LEFT JOIN (SELECT cust_id,
          Sum(payment_pr) bd_pr
   FROM   TB_CUST_PAYMENT_HIS
   WHERE  payment_date LIKE Concat('%', $Date_format_Now, '%')
          AND payment_his_cd = 'BB'
   GROUP  BY cust_id) AS bb_his
ON bd_his.cust_id = bb_his.cust_id) bd_pay
/*이번달 입금액*/
ON cust.cust_id = bd_pay.cust_id
JOIN TB_CUST_PAYMENT cust_pay
ON cust.cust_id = cust_pay.cust_id
join TB_GRADE_CLASS_CD gcd on cust.GRADE_CLASS_CD = gcd.GRADE_CLASS_CD
WHERE  sel.seller_id IS NULL
       AND cust.activ_yn = 1 and cust.cust_id = ?
GROUP  BY cust.cust_id $yn_tow");
              $stmt->bind_param("s",$cust_id);

              if ($yn == "UPDATE") {
                if ($stmt->execute()) {
                    return UPDATE_COMPLETED;
                } else {
                    return UPDATE_INSERT_FAILED;
                }
              }else {
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    return $stmt;
                } else {
                    return SELECT_FAILED;
                }
              }
          }

          public function select_now_grade($cust_id)//현재등급확인
          {
            $stmt = $this->conn->prepare("SELECT GRADE_CLASS_CD  FROM TB_CUST WHERE activ_yn = 1 and cust_id = ?");
            $stmt->bind_param("s",$cust_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return SELECT_FAILED;
            }
          }

          public function select_egg($cust_id)
          {
              $stmt = $this->conn->prepare("SELECT *  FROM TB_COUPON
                 WHERE (COUPON_CLASS_CD LIKE 'ABA' or COUPON_CLASS_CD LIKE 'ABJ'or COUPON_CLASS_CD LIKE 'ABM') AND CUST_ID LIKE ?");
              $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt->num_rows;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function tb_cust_cust()
          {
              $stmt = $this->conn->prepare("SELECT cust_id  FROM TB_CUST WHERE activ_yn = 1");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //유통사별 매칭업장검색(영업지표)
          public function adminMatchSelUserList($date)
          {
              $stmt = $this->conn->prepare("SELECT bys.CUST_ID as 고객아이디,cust.BUSINESS_NAME as 고객명,
                                            bys.SELLER_ID as 유통사아이디 , sel.SELLER_NAME as 유통사명,
                                            cust.TEL_NO as 전화번호,cust.ADDR_CONT as 주소,cust.DELIV_POSITION as 성수업장,
                                            login.logIn as 등록날짜,logde.logDe as 삭제날짜
                                            -- cust.TEL_NO as 전화번호,cust.ADDR_CONT as 주소,login.log_cond as 성수업장
                                            FROM TB_SELLER_BY_CUST bys
                                            join TB_SELLER sel on bys.seller_id = sel.seller_id
                                            join     (
                                                SELECT ct.* from TB_CUST ct
                                                join (SELECT * from TB_ORDER WHERE REG_DATE >= '$date' group by cust_id) ord
                                                on ct.cust_id = ord.cust_id where ct.activ_yn = 1
                                                and ct.business_name not like '%테스트%'
                                                ) cust
                                            on bys.cust_id = cust.cust_id
                                            left join (SELECT *,max(LOG_DATE) as logIn from TB_SELLER_BY_CUST_LOG_HIS where LOG_COND = '등록' group by cust_id,seller_id) login
                                            on concat(bys.cust_id,'_',bys.seller_id) = concat(login.cust_id,'_',login.seller_id)
                                            left join (SELECT *,max(LOG_DATE) as logDe from TB_SELLER_BY_CUST_LOG_HIS where LOG_COND = '삭제' group by cust_id,seller_id) logde
                                            on concat(bys.cust_id,'_',bys.seller_id) = concat(logde.cust_id,'_',logde.seller_id)");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function adminMsdbCodeEcho()
          {
              $stmt = $this->conn->prepare("SELECT
                (SELECT count(prod_cd) FROM TB_PROD) as MSDB개수
               FROM dual WHERE 1");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          public function adminMsSelMatchiCodeEcho()
          {
              $stmt = $this->conn->prepare("SELECT
                SELLER_NAME,
                (SELECT count(*) from TB_SELLER_PROD_PRICE where SELLER_ID = sel.SELLER_ID) as 유통코드수,
                (SELECT count(*) from TB_SELLER_PROD_CD where SELLER_ID = sel.SELLER_ID and replace(SELLER_PROD_CD,' ','') != '') as 매칭코드수
               FROM TB_SELLER sel WHERE
               ACTIV_YN LIKE '1'
               -- SELLER_ID LIKE '1018130747' or
               -- SELLER_ID LIKE '1078176324' or
               -- SELLER_ID LIKE '1248531373' or
               -- SELLER_ID LIKE '3128125280' or
               -- SELLER_ID LIKE '6038111270' or
               -- SELLER_ID LIKE '1258544565'
               ");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function admin_salse_echo($order_by)
          {
            //(SELECT c.* from TB_CUST c join TB_SELLER_BY_CUST sc on c.cust_id = sc.cust_id)
              $stmt = $this->conn->prepare("SELECT cust.cust_id,
       cust.business_name,
       cust.reg_date       AS 가입일,
       Min(ord.order_date) AS 첫주문,
       Max(ord.order_date) AS 마지막주문,
Datediff(Max(ord.order_date), Min(ord.order_date)) AS 첫주문부터마지막주문까지일수, (SELECT
Count(o.order_no) FROM (SELECT cust_id, order_no FROM TB_ORDER WHERE
order_cond_cd = '01') o JOIN (SELECT order_no FROM TB_ORDER_ITEM WHERE (
order_cond_cd = '01' OR order_cond_cd = '02' OR order_cond_cd = '03') GROUP BY
order_no) i ON o.order_no = i.order_no WHERE o.cust_id = cust.cust_id GROUP BY
o.cust_id) AS 누적주문일수,
(SELECT sum(ii.prod_order_cnt*ii.order_pay-ii.coupon_price) from TB_ORDER_ITEM ii join TB_ORDER oo
on ii.order_no = oo.order_no where oo.cust_id = cust.cust_id ) as 총결제금액,cust.DELIV_POSITION as 성수동여부
FROM   TB_CUST cust
       JOIN TB_CUST_PAYMENT pay
         ON cust.cust_id = pay.cust_id
       LEFT JOIN (SELECT * FROM TB_ORDER WHERE order_cond_cd = '01') ord
              ON cust.cust_id = ord.cust_id
       LEFT JOIN TB_ORDER_ITEM item
              ON ord.order_no = item.order_no
      GROUP BY cust.cust_id ORDER  BY $order_by");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function admin_general_echo($echo_date)
          {
            $befor1time= date("Y-m",strtotime("$echo_date -1 months"));
            $befor2tiem = date("Y-m",strtotime("$echo_date -2 months"));

              $stmt = $this->conn->prepare("SELECT
(SELECT sum((item.order_pay*item.PROD_ORDER_CNT)-item.coupon_price) from TB_ORDER_ITEM item
join TB_ORDER ord on item.ORDER_NO = ord.ORDER_NO
where ord.ORDER_DATE like '%$befor1time%'
and (item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03')) as 현매출 ,

(SELECT count(id_cnt) from(
SELECT count(cust_id) as id_cnt FROM
TB_ORDER
WHERE ORDER_DATE LIKE '%$befor1time%'
and ORDER_COND_CD = '01' group by cust_id) as usetable) as 이용자수,


(SELECT count(joinCust) from(
SELECT cust.cust_id as joinCust FROM TB_CUST cust left join
(SELECT * from TB_SELLER_BY_CUST group by cust_id) by_cust on cust.cust_id = by_cust.cust_id
WHERE cust.activ_yn = 1 and cust.reg_date like '%$befor1time%') as jointable) as 거래계약수,

(SELECT count(minDate) from(
SELECT min(ord.ORDER_DATE) as minDate FROM TB_ORDER ord where ord.ORDER_COND_CD = '01'
group by ord.cust_id HAVING minDate like '%$befor1time%') as onetable) as  첫거래발생,

(SELECT count(maxDate) from(
SELECT (Datediff(Max(ord.order_date), Min(ord.order_date))/(SELECT
Count(o.order_no) FROM (SELECT cust_id, order_no FROM TB_ORDER WHERE
order_cond_cd = '01') o JOIN (SELECT order_no FROM TB_ORDER_ITEM WHERE (
order_cond_cd = '01' OR order_cond_cd = '02' OR order_cond_cd = '03') GROUP BY
order_no) i ON o.order_no = i.order_no WHERE o.cust_id = ord.cust_id GROUP BY
o.cust_id)) as difdate,Datediff(LAST_DAY(DATE_ADD(now(), INTERVAL -1 MONTH)),max(ord.ORDER_DATE)) as maxdif,max(ord.ORDER_DATE)  as maxDate
FROM TB_ORDER ord where ord.ORDER_COND_CD = '01'
group by ord.cust_id HAVING  maxDate like concat('%','$befor2tiem','%')) as maxtable) as 고객이탈,
(SELECT count(*) as cnt from (SELECT item.order_no
  FROM TB_ORDER_ITEM item  join TB_ORDER ord on item.order_no = ord.order_no
  left join TB_PROD prd on item.prod_cd = prd.prod_cd
  WHERE ord.order_date like concat('%','$befor1time','%') and item.order_cond_cd ='03'
  group by item.order_no,item.seller_id) as final_order_cnt) as 주문건수,

  (SELECT  concat(round((count(fav.prod_cd)/count(item.prod_cd) * 100 ),2),'%')
  FROM (SELECT * from TB_ORDER_ITEM group by SELLER_ID ,PROD_CD) item join TB_ORDER ord on item.order_no = ord.order_no
  left join (SELECT * FROM TB_FAVOR_PROD
  group by PROD_CD , seller_id) fav on item.seller_id = fav.seller_id and item.prod_cd = fav.prod_cd
  WHERE ord.reg_date like '%$befor1time%') as 즐겨찾기비율
from dual");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //MS코드 유통업체 첫주문 상품 체크!
          public function select_Oneprod_check($prod_cd,$seller,$order_date)
          {
              $stmt = $this->conn->prepare("SELECT count(item.PROD_CD)
              FROM TB_ORDER_ITEM item  left join TB_ORDER ord on item.order_no = ord.ORDER_NO
              WHERE item.PROD_CD = '$prod_cd' and item.seller_id like '$seller' and ord.ORDER_DATE <= '$order_date'");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //특가상품 첫주문 및 중복주문 체크
          public function select_Event_Oneprod_check($cust_id,$prod_cd,$seller,$nowDate,$nowDateTime)
          {
            //DATE_ADD('$nowDate 16:00:00', INTERVAL '-24' HOUR) 전날 4시
            //'$nowDate 16:00:00' 오늘 4시
            //'$nowDateTime' 오늘 시간
            //DATE_ADD('$nowDate 16:00:00', INTERVAL '+24' HOUR) 다음날 4시


              //4시 마감의 건 확인하기
              // $stmt = $this->conn->prepare("SELECT count(item.PROD_CD)
              // FROM TB_ORDER_ITEM item  left join TB_ORDER ord on item.order_no = ord.ORDER_NO
              // WHERE ord.cust_id = '$cust_id' and item.PROD_CD = '$prod_cd' and item.seller_id like '$seller'
              // and ord.ORDER_DATE <= '$nowDate 16:00:00'
              // and '$nowDate 16:00:00' > '$nowDateTime'");
              //지금시간 > 지금 3시 28분
              // if ($prod_cd == "E0000001") {
              //   $stmt = $this->conn->prepare("SELECT count(item.PROD_CD),max(ord.order_date) as maxDate
              //   FROM TB_ORDER_ITEM item  left join TB_ORDER ord on item.order_no = ord.ORDER_NO
              //   WHERE ord.cust_id = '$cust_id' and item.PROD_CD = '$prod_cd' and item.seller_id like '$seller'
              //   and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03' or item.order_cond_cd = '04')
              //   group by ord.cust_id");
              // }elseif ($prod_cd == "E0000000") {
                $stmt = $this->conn->prepare("SELECT sum(item.PROD_ORDER_CNT),max(ord.order_date) as maxDate
                FROM TB_ORDER_ITEM item  left join
                (SELECT * from TB_ORDER
                  where order_date between
                  IF(date_format('$nowDateTime','%Y-%m-%d %T') > date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
                    date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
                    date_format(DATE_ADD('$nowDate 16:00:00',INTERVAL '-24' HOUR),'%Y-%m-%d %T')
                  )
                   and
                  IF(date_format('$nowDateTime','%Y-%m-%d %T') > date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
                  date_format(DATE_ADD('$nowDate 16:00:00',INTERVAL '+24' HOUR),'%Y-%m-%d %T'),
                    date_format('$nowDate 16:00:00','%Y-%m-%d %T')
                  )
                )ord on item.order_no = ord.ORDER_NO
                WHERE ord.cust_id = '$cust_id' and item.PROD_CD = '$prod_cd' and item.seller_id like '$seller'
                and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03' or item.order_cond_cd = '04')
                group by ord.cust_id");
              // }else {
              //   $stmt = $this->conn->prepare("SELECT count(item.PROD_CD),max(ord.order_date) as maxDate
              //   FROM TB_ORDER_ITEM item  left join
              //   (SELECT * from TB_ORDER
              //     where order_date between
              //     IF(date_format('$nowDateTime','%Y-%m-%d %T') > date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
              //       date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
              //       date_format(DATE_ADD('$nowDate 16:00:00',INTERVAL '-24' HOUR),'%Y-%m-%d %T')
              //     )
              //      and
              //     IF(date_format('$nowDateTime','%Y-%m-%d %T') > date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
              //     date_format(DATE_ADD('$nowDate 16:00:00',INTERVAL '+24' HOUR),'%Y-%m-%d %T'),
              //       date_format('$nowDate 16:00:00','%Y-%m-%d %T')
              //     )
              //   )ord on item.order_no = ord.ORDER_NO
              //   WHERE ord.cust_id = '$cust_id' and item.PROD_CD = '$prod_cd' and item.seller_id like '$seller'
              //   and (item.order_cond_cd = '00' or item.order_cond_cd = '01' or item.order_cond_cd = '02' or item.order_cond_cd = '03' or item.order_cond_cd = '04')
              //   group by ord.cust_id");
              // }


              // and ord.order_date between
              // IF(DATE('$nowDateTime') > DATE('$nowDate 16:00:00'),
              //   date_format('$nowDate 16:00:00','%Y-%m-%d %T'),
              //   date_format(DATE_ADD('$nowDate 16:00:00',INTERVAL '-24' HOUR),'%Y-%m-%d %T')
              // )
              //  and
              // IF(DATE('$nowDateTime') > DATE('$nowDate 15:28:00'),
              // date_format(DATE_ADD('$nowDate 16:00:00',INTERVAL '+24' HOUR),'%Y-%m-%d %T'),
              //   date_format('$nowDate 16:00:00','%Y-%m-%d %T')
              // )


              // echo "$nowDate";

              // HAVING maxDate between
              // DATE_ADD('$nowDate 16:00:00',INTERVAL '-24' HOUR)
              //  and
              // DATE('$nowDate 16:00:00')


              // date(max(ord.order_date)) between DATE_ADD('$nowDate 16:00:00', INTERVAL '-24' HOUR) and '$nowDate 16:00:00'

              // SELECT count(item.PROD_CD)
              // FROM TB_ORDER_ITEM item  left join TB_ORDER ord on item.order_no = ord.ORDER_NO
              // WHERE ord.cust_id = '1234512345' and item.PROD_CD = 'E0000000' and item.seller_id like 'deliverylab'
              //  and ord.ORDER_DATE <= '2020-07-13 16:00:00' -->주문시간이 4시보다  작으면서
              // and '2020-07-13 16:00:00' > "2020-07-13 15:41:00" --> 현재시간이 4시보다 작은것
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function select_all_groupDate()
          {
              $stmt = $this->conn->prepare("SELECT date_format(reg_date,'%Y-%m-01') as formatDate
              FROM TB_ORDER WHERE ORDER_COND_CD = '01' group by formatDate
              having formatDate > '2019-04' ");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //검색후 insert
          //현대/한화/동원/CJ/아워홈
          //
          public function insert_sdg($cust_id,$sel,$margin,$min_pr)
          {
            // $sdg_seller = ["1248531373","1018130747","3128125280","6038111270","1078176324"];
            // foreach ($sdg_seller as $key => $value) {
                $stmt = $this->conn->prepare("INSERT IGNORE INTO TB_SELLER_BY_CUST(CUST_ID,SELLER_ID,MARGIN_RATE,MIN_ORDER_PR) VALUES ('$cust_id','$sel',$margin,$min_pr)");
                if ($stmt->execute()) {
                    $this->insertByLogHis('등록',$cust_id,$sel,$margin,$min_pr);
                    return INSERT_COMPLETED;
                } else {
                    return INSERT_FAILED;
                }
            // }
          }

          public function update_sdg($cust_id,$addr)
          {
            // $sdg_seller = ["1248531373","1018130747","3128125280","6038111270","1078176324"];
            // foreach ($sdg_seller as $key => $value) {
                $stmt = $this->conn->prepare("UPDATE TB_CUST SET DELIV_POSITION = '$addr' WHERE CUST_ID = '$cust_id'");
                if ($stmt->execute()) {
                    return UPDATE_COMPLETED;
                } else {
                    return UPDATE_FAILED;
                }
            // }
          }

          public function selectMSuseritemSdg($custId,$sel_id)
          {
              $stmt = $this->conn->prepare("SELECT sel_cd.PROD_CD, sel_cd.SELLER_ID,sel.seller_name, sel_cd.SELLER_PROD_CD,
                                round(sel_pr.SELLER_PROD_PRICE/0.95,-1),sel_pr.SELLER_PROD_NAME,pt.prod_cont,pt.prod_wgt,pt.fact_name,pt.prod_name,
                                IFNULL(dcreg.DISCOUNT_RATE,0.0) FROM TB_SELLER_PROD_CD sel_cd
                                join TB_SELLER_PROD_PRICE sel_pr on concat(sel_cd.seller_id,'_',sel_cd.SELLER_PROD_CD) =
                                concat(sel_pr.seller_id,'_',sel_pr.SELLER_PROD_CD) join TB_SELLER sel on sel_cd.SELLER_ID =
                                sel.SELLER_ID left join (SELECT * from TB_PROD_DISCOUNT where cust_id = '$custId') dc
                                on sel_cd.prod_cd=dc.prod_cd and sel_cd.seller_id=dc.seller_id
                                join TB_PROD pt on sel_cd.prod_cd = pt.prod_cd
                                left join TB_DISCOUNT_PROD_REG dcreg on sel_cd.PROD_CD = dcreg.PROD_CD and sel_cd.SELLER_ID = dcreg.SELLER_ID
                                WHERE sel_cd.seller_id = '$sel_id' and  dc.prod_cd is null and sel_cd.SELLER_PROD_CD=sel_pr.SELLER_PROD_CD and
                                sel_cd.SELLER_ID in (SELECT seller_id from TB_SELLER_BY_CUST where cust_id = '$custId')");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function selectEventProdCont($prod_cd,$sel_id)
          {
              $stmt = $this->conn->prepare("SELECT prd.STN_COND_CD,prd.PROD_CD,prd.PROD_NAME,prd.FACT_NAME,prd.PROD_WGT,
                prd.prod_cont,prc.seller_id,prc.seller_prod_price,sel.SELLER_CONT,prc.ORDER_DEADLINE_TM FROM
                (SELECT * from TB_SELLER_PROD_PRICE where SELLER_PROD_CD like '$prod_cd' and SELLER_ID = '$sel_id') prc
                join TB_PROD prd on prc.seller_prod_cd = prd.prod_cd join TB_SELLER sel on prc.seller_id = sel.SELLER_ID");

              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //selectMSuseritemSdg

          //MS코드 유통업체 첫주문 상품 체크!
          public function selectOneOrderCust($cust,$order_date)
          {
              $stmt = $this->conn->prepare("SELECT count(ord.ORDER_NO),cust.DELIV_POSITION
              FROM  TB_ORDER ord join TB_CUST cust on ord.cust_id = cust.cust_id
              join (SELECT * from TB_ORDER_ITEM group by order_no) item on ord.order_no = item.order_no
              WHERE ord.CUST_ID = '$cust' and ord.ORDER_DATE < '$order_date'");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //자동즐겨찾기상품 검색
          public function selectSdgFavor()
          {
              $stmt = $this->conn->prepare("SELECT PROD_CD,SELLER_ID FROM TB_FAVOR_PROD_REG");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //특가상품리스트
          public function specialProdList()
          {
              $stmt = $this->conn->prepare("SELECT PROD_CD,SELLER_ID,MAX_ORDER_AM,ACTIV_YN FROM TB_SPECIAL_PROD WHERE PROD_CD LIKE '%E%' AND ACTIV_YN LIKE 'Y'");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //특가상품리스트
          public function specialProdListJoin()
          {
              $stmt = $this->conn->prepare("SELECT prd.prod_cd,prd.CLASS_CD,cc.CLASS_NAME,
                prd.CLASS_DETAIL_CD,cc2.CLASS_NAME,prd.PROD_NAME,prd.PROD_CONT,
                prd.PROD_WGT,prd.FACT_NAME,prd.TAXFREE_YN,prd.STN_COND_CD,sc.STN_COND_NAME,
                prd.ORDER_DEADLINE_TM,sp.no,sp.MAX_ORDER_AM,sp.ACTIV_YN FROM
                TB_SPECIAL_PROD sp join TB_PROD prd on sp.PROD_CD = prd.PROD_CD
                left join TB_CLASS_CD cc on prd.CLASS_CD = cc.CLASS_CD
                left join TB_CLASS_CD cc2 on prd.CLASS_DETAIL_CD = cc2.CLASS_CD
                join TB_STN_COND sc on prd.STN_COND_CD = sc.STN_COND_CD");
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //특가상품업데이트
          public function updateSpecialUseYn($request)
          {
            $NO = $request["no"];//특가상품 번호
            $USE_YN = $request["check"];//특가상품 정보 번
            $str = "UPDATE TB_SPECIAL_PROD SET ACTIV_YN = '$USE_YN' WHERE NO = $NO";
            $stmt = $this->conn->prepare("$str");
            // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
            // $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
                return UPDATE_COMPLETED;
                // return print_r($request,true);
            } else {
                return UPDATE_FAILED;
            }
          }
          //특가상품최대수량업데이트
          public function updateSpecialMaxAm($request)
          {
            $NO = $request["no"];//특가상품 번호
            $MAX = $request["max_order_am"];//특가상품 정보 번
            $str = "UPDATE TB_SPECIAL_PROD SET MAX_ORDER_AM = '$MAX' WHERE NO = $NO";
            $stmt = $this->conn->prepare("$str");
            // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
            // $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
                return UPDATE_COMPLETED;
                // return print_r($request,true);
            } else {
                return UPDATE_FAILED;
            }
          }
                // $prod_cd,$class_cd,$class_name,
                // $class_detail_cd,$class_name,$prod_name,$prod_cont,
                // $prod_wgt,$fact_name,$taxfree_yn,$stn_cond_cd,$stn_cond_name,
                // $order_deadline_tm,$max_order_am,$activ_yn
          public function selectSpecialClassSeller($cust_id)
          {
              $stmt = $this->conn->prepare("SELECT  cls_cd.class_name from TB_PROD_DISCOUNT dc join TB_PROD prd on dc.prod_cd = prd.prod_cd join TB_CLASS_CD cls_cd
              on prd.class_cd = cls_cd.class_cd where dc.cust_id = ? and dc.seller_id like '%eventstore%' group by prd.class_cd");
      //substring(dc.prod_cd,1,3)
              $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return CLASS_NOT_EXIST;
              }
          }

          public function selectSpecialList($cust_id,$cust_id2,$cust_id3,$start,$list,$class_name,$sel)
          {
            $class_where="";
            $class_join="";
            $selWhere="";
            // echo "$cust_id,$cust_id2,$cust_id3,$start,$list,$class_name,$sel";
            // $all_by = "(CASE pt.prod_wgt * 1 WHEN pt.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),pt.prod_wgt *1 asc,pt.prod_name,pt.prod_cont";
            // $all_by = "(CASE pt.prod_wgt * 1 WHEN pt.prod_wgt *1 REGEXP '[0-9]' THEN 1 ELSE 3 END),pt.prod_wgt *1 asc, pt.fact_name asc,pt.prod_name,pt.prod_cont,price asc";
            $all_by = "pt.prod_name asc,pt.prod_cont asc,pt.fact_name asc,pt.prod_wgt asc,price asc";
            if ($class_name == "" || !isset($class_name) || empty($class_name) || $class_name == "ALL") {
            }else {
              $class_where = "and cs_cd.CLASS_NAME like '$class_name' ";
              $class_join = "left join TB_CLASS_CD cs_cd on pt.class_cd = cs_cd.class_cd ";
            }
            if (isset($sel) && $sel !=="") {
              $selWhere = " and sel.seller_id = '$sel' ";
            }
            // round(sel_price.SELLER_PROD_PRICE*(1-(selcust.MARGIN_RATE/100))) 할인율.
          //round(sel_price.SELLER_PROD_PRICE+((sel_price.SELLER_PROD_PRICE/100)*selcust.MARGIN_RATE),-1) +퍼센트
          // if ($cust_id == "1234567890") {
          //   return PRODUCT_NOT_EXIST;
          // }
              $stmt = $this->conn->prepare("SELECT pt.stn_cond_cd,discunt.prod_cd,fav.prod_cd,pt.prod_name,
      pt.prod_cont,pt.prod_wgt,pt.sale_unit,
      if(INSTR('$this->JinhyunPom',sel_cd.seller_id),if(pt.TAXFREE_YN = 0,round(sel_price.SELLER_PROD_PRICE*1.1),sel_price.SELLER_PROD_PRICE),(
      if(pt.prod_cd = discunt.prod_cd
        ,if(pt.TAXFREE_YN = 0
          ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1)*1.1)
          ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1)*((100-discunt.DISCOUNT_RATE)*0.01),-1))
        ,if(pt.TAXFREE_YN = 0
          ,round(round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1),-1)*1.1)
          ,round(round((sel_price.SELLER_PROD_PRICE/0.95)/((100-selcust.MARGIN_RATE)*0.01),-1))))))  as price,
      pt.fact_name,pt.fact_name,concat(sel_cd.prod_cd,'_',sel_cd.seller_id),
      concat(sel_cd.prod_cd,'_',sel_cd.seller_id),sel.seller_name,sel_cd.seller_id,sel_price.ORDER_DEADLINE_TM,sel_price.point_order_yn
      from TB_PROD pt join TB_SELLER_PROD_CD sel_cd on pt.prod_cd = sel_cd.prod_cd
      LEFT OUTER JOIN TB_PROD_SORT sort ON sort.PROD_CD = pt.PROD_CD
      left join TB_SELLER_PROD_PRICE sel_price on
      concat(sel_cd.seller_id,'_',sel_cd.seller_prod_cd) = concat(sel_price.seller_id,'_',sel_price.seller_prod_cd)
      join TB_SELLER sel on sel_cd.seller_id = sel.seller_id
      join (SELECT * from TB_SELLER_BY_CUST where cust_id = ?) selcust on sel.seller_id = selcust.seller_id
      join (SELECT dcs.* from (SELECT * from TB_PROD_DISCOUNT WHERE SELLER_ID like '%eventstore%') dcs  where cust_id = ?) discunt on sel.seller_id=discunt.seller_id and pt.prod_cd=discunt.prod_cd
      left join (SELECT prod_cd,seller_id from TB_FAVOR_PROD where cust_id =?) fav on concat(sel_cd.prod_cd,'_',sel_cd.seller_id) = concat(fav.prod_cd,'_',fav.seller_id)
      $class_join
      where sel_cd.SELLER_PROD_CD = sel_price.SELLER_PROD_CD $class_where $selWhere order by sort.PROD_RANKING ASC
      LIMIT $start,$list");
      //sel_cd.prod_cd asc,sel_cd.seller_id asc
      //concat(sel.seller_id,'_',pt.prod_cd) = concat(discunt.seller_id,'_',discunt.prod_cd)
              $stmt->bind_param("sss",$cust_id,$cust_id2,$cust_id3);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return PRODUCT_NOT_EXIST;
              }
          }


          //자동즐겨찾기상품 검색
          public function sosTEST($sos,$type)
          {
            switch ($type) {
              case 'prd':
                // $stmt = $this->conn->prepare("SELECT prd.prod_name from TB_PROD prd
                // where prd.prod_name REGEXP
                // 'XO소스|가라아게|게|게다리|게맛살|게살|계|계란후라이|과일|과자|김밥|김치찌개|꽃게|냉면|뉴슈가|단무지|단호|단호박|돈까스|돼지|듀럼밀|딸기잼|라면|롤|루이보스|마늘바게트|마늘빵|마파람|모시조개|미트볼|미트소스|바게|바게트|바리스|박스|베이글|부대찌개|부라보|부침가|불고기양념|비빔면|사리곰탕|사리곰탕면|샌드위치|생라면|샬롯|성게|성게알|소불고기|순두부|순살치킨|스파게티|스파게티니|스파게티면|스파게티소스|식빵|신라면|알감자|열라면|열무김치|옥수수|와인|와플|우니|유부|잉글리쉬머핀|자숙|절단꽃게|젓갈|조랭이떡|죽|쥬키니|진라면|진라면매운맛|집게|짜파게티|찌개|차돌박이|채끝|초밥|치킨가라아게|치킨파우더|카레|카카오|콩자반|쿠|크라비아|크래미|타코야끼|토마토소스|통감자|튀김|트콘|파스타|파스타면|파워에이드|포카|피망|핫도그번'");
                $stmt = $this->conn->prepare("SELECT prd.prod_name from TB_PROD prd
                where prd.prod_name REGEXP
                (SELECT REPLACE(GROUP_CONCAT(KEYWORD),',','|') AS NAME
                FROM TB_PROD_SEARCH_KEYWORD WHERE KEYWORD_SEARCH LIKE '%$sos%')");
                break;
              case 'group':
                // $stmt = $this->conn->prepare("SELECT prd.prod_name from TB_PROD prd
                // where prd.prod_name REGEXP
                // 'XO소스|가라아게|게|게다리|게맛살|게살|계|계란후라이|과일|과자|김밥|김치찌개|꽃게|냉면|뉴슈가|단무지|단호|단호박|돈까스|돼지|듀럼밀|딸기잼|라면|롤|루이보스|마늘바게트|마늘빵|마파람|모시조개|미트볼|미트소스|바게|바게트|바리스|박스|베이글|부대찌개|부라보|부침가|불고기양념|비빔면|사리곰탕|사리곰탕면|샌드위치|생라면|샬롯|성게|성게알|소불고기|순두부|순살치킨|스파게티|스파게티니|스파게티면|스파게티소스|식빵|신라면|알감자|열라면|열무김치|옥수수|와인|와플|우니|유부|잉글리쉬머핀|자숙|절단꽃게|젓갈|조랭이떡|죽|쥬키니|진라면|진라면매운맛|집게|짜파게티|찌개|차돌박이|채끝|초밥|치킨가라아게|치킨파우더|카레|카카오|콩자반|쿠|크라비아|크래미|타코야끼|토마토소스|통감자|튀김|트콘|파스타|파스타면|파워에이드|포카|피망|핫도그번'");
                $stmt = $this->conn->prepare("SELECT LENGTH(GROUP_CONCAT(KEYWORD SEPARATOR '|')),GROUP_CONCAT(KEYWORD SEPARATOR '|') AS NAME
                FROM TB_PROD_SEARCH_KEYWORD WHERE KEYWORD_SEARCH like '%$sos%'");
                break;

              default:
                $stmt = $this->conn->prepare("SELECT REPLACE(GROUP_CONCAT(KEYWORD),',','|') AS NAME
                FROM TB_PROD_SEARCH_KEYWORD WHERE KEYWORD_SEARCH like '%$sos%'");
                break;
            }
              // $stmt->bind_param("s",$cust_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function group_concat_max_len()
          {
              $stmt = $this->conn->prepare("SET SESSION group_concat_max_len = 102400");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return CLASS_NOT_EXIST;
              }
          }
          public function selectCreitLimit($cust_id)
          {
            $stmt = $this->conn->prepare("SELECT credit_limit from TB_CUST_PAYMENT WHERE cust_id = ?");
            $stmt->bind_param("s", $cust_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                return $stmt;
            } else {
                return ACCOUNT_NOT_EXIST;
            }
          }


          public function updateVwOrder($order_no,$cond_cd)
          {
              // echo "$code,$cust_id";
              $stmt = $this->conn->prepare("UPDATE TB_ORDER_ITEM SET order_cond_cd = ? where order_no = ?");
              $stmt->bind_param("si",$cond_cd,$order_no);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }
          public function updateVwOrderDate($order_no,$cond_cd)
          {
              // echo "$code,$cust_id";
              $stmt = $this->conn->prepare("UPDATE TB_ORDER SET order_cond_cd = ? where order_no = ?");
              $stmt->bind_param("si",$cond_cd,$order_no);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }

          //입금통보 insert
          public function insertDepositAccnInfo($DEPOSIT_YN,$DEPOSIT_CD,$WTID,$BN_CD,$DEPOSIT_COND)
          {//입금여부,입금코드,WTID,통장코드
              $stmt = $this->conn->prepare("INSERT INTO TB_DEPOSIT_ACCN_INFO(DEPOSIT_YN,DEPOSIT_CD,WTID,BN_CD,REG_DATE,DEPOSIT_COND) VALUES (?,?,?,?,now(),?)");
              $stmt->bind_param("sssss",$DEPOSIT_YN,$DEPOSIT_CD,$WTID,$BN_CD,$DEPOSIT_COND);
              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }
          }
          //입금통보 코드검색
          public function selectDepositAccnYN($wtid)
          {
              $stmt = $this->conn->prepare("SELECT DEPOSIT_YN,DEPOSIT_CD,BN_CD,REG_DATE FROM TB_DEPOSIT_ACCN_INFO WHERE WTID = ?");
              $stmt->bind_param("s",$wtid);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //입금통보 오더검색
          public function selectDepositAccnOrder($wtid)
          {
              $stmt = $this->conn->prepare("SELECT ord.ORDER_NO,ord.ORDER_DATE,ord.ORDER_COND_CD,ord.CUST_ID,
                ord.REG_DATE,ord.UPDATE_DATE,ord.memo,ord.wtid,ct.BUSINESS_NAME from TB_ORDER ord
                join  TB_CUST ct on ord.CUST_ID = ct.CUST_ID WHERE WTID like '%$wtid%'");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //입금통보 계좌검색
          public function selectDepositWtidBn($order_no)
          {
              $stmt = $this->conn->prepare("SELECT BN_NAME 은행명,SUBSTRING_INDEX(SUBSTRING_INDEX(wtid, '@', -2), '@', 1) 계좌번호,
              wtid FROM TB_ORDER ord join TB_BN_CD bn on SUBSTRING_INDEX(SUBSTRING_INDEX(wtid, '@', -2), '@', -1) = bn.BN_CD
              WHERE order_no = $order_no");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //입금시간 업데이트
          public function updateOrderDepositDate($order_no)
          {
              $stmt = $this->conn->prepare("UPDATE TB_ORDER SET UPDATE_DATE = now() WHERE ORDER_NO = '$order_no'");
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }


          public function insertByLogHis($log_cond,$cust_id,$seller_id,$margin_rate,$min_pr)
          {
              // echo "$code,$cust_id";
              $stmt = $this->conn->prepare("INSERT IGNORE INTO TB_SELLER_BY_CUST_LOG_HIS(LOG_DATE, LOG_COND, CUST_ID,
                SELLER_ID, MARGIN_RATE, MIN_ORDER_PR)
                VALUES (now(),?,?,?,?,?)");
              $stmt->bind_param("sssii",$log_cond,$cust_id,$seller_id,$margin_rate,$min_pr);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }
          public function updateArriveChange($arrive,$order_no,$sel,$tm)
          {
              // echo "$code,$cust_id";
              $stmt = $this->conn->prepare("UPDATE TB_ORDER_ITEM SET
                ARRIVE_DATE='$arrive' WHERE ORDER_NO = '$order_no'
                and SELLER_ID = '$sel'
                and ORDER_DEADLINE_TM = '$tm'");
              // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
              if ($stmt->execute()) {
                  return UPDATE_COMPLETED;
              } else {
                  return UPDATE_FAILED;
              }
          }

          public function insertInto($info)
          {
              $stmt = $this->conn->prepare("INSERT INTO TB_TEST_INFO (info,reg_date) values (?,now())");
              $stmt->bind_param("s", $info);
              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }
          }

          //쿠키검색
          public function selectCookieTm($prd,$sel)
          {
              $stmt = $this->conn->prepare("SELECT ORDER_DEADLINE_TM from
                TB_SELLER_PROD_PRICE pr JOIN
                (SELECT * from TB_SELLER_PROD_CD where prod_cd = '$prd' and seller_id = '$sel') selCd
                on pr.SELLER_PROD_CD = selCd.SELLER_PROD_CD");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //장바구니 D-Day 검색
          public function selectCartTm($cust_id,$sel)
          {
              $stmt = $this->conn->prepare("SELECT cart.seller_id,cart.order_deadline_TM,cart.DELIV_PAY FROM
    (SELECT ct.*,selCd.SELLER_PROD_CD,prc.ORDER_DEADLINE_TM,sel.DELIV_PAY from TB_CART ct JOIN TB_SELLER_PROD_CD selCd
     on ct.prod_cd = selCd.PROD_CD and ct.seller_id = selCd.SELLER_ID
     join TB_SELLER_PROD_PRICE prc
      on  selCd.SELLER_ID = prc.SELLER_ID
    join (SELECT SELLER_ID,DELIV_PAY FROM TB_SELLER) sel
      on  ct.SELLER_ID = sel.SELLER_ID
      and selCd.SELLER_PROD_CD = prc.SELLER_PROD_CD
     where ct.CUST_ID = '$cust_id' and ct.SELLER_ID = '$sel') cart
     group by cart.seller_id,cart.order_deadline_tm
     order by cart.seller_id asc,cart.order_deadline_TM asc");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //최소주문금액 및 배송비조회
          public function selectDlPay($cust,$sel)
          {
              $stmt = $this->conn->prepare("SELECT sel.DELIV_PAY,bys.MIN_ORDER_PR
                FROM TB_SELLER sel join TB_SELLER_BY_CUST bys on sel.SELLER_ID = bys.SELLER_ID
                where bys.CUST_ID = '$cust' and bys.SELLER_ID = '$sel'");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }
          //결제로그남기기
          public function insertPaymentLog($WTID,$RESULT_CD,$CUST_ID,$URL_INFO,$PAYMENT_TYPE,$PARAMETER_INFO)
          {
                $stmt = $this->conn->prepare("INSERT INTO TB_PAYMENT_LOG (WTID, RESULT_CD, CUST_ID, URL_INFO, PAYMENT_TYPE, PARAMETER_INFO)
                VALUES ('$WTID','$RESULT_CD','$CUST_ID','$URL_INFO','$PAYMENT_TYPE','$PARAMETER_INFO');");

              $stmt->bind_param("ssi", $coupon,$cust_id,$day);
              if ($stmt->execute()) {
                  return INSERT_COMPLETED;
              } else {
                  return INSERT_FAILED;
              }
          }
          //결제로그 view
          public function admin_order_card($s_point,$list,$toDay)
          {
            if (isset($s_point) && isset($list)) {
              $limit = "limit $s_point,$list";
            }else {
              $limit = "";
            }
            if (isset($toDay)) {
              $days = "where log.PAYMENT_TM like '%$toDay%'";
            }else {
              $days = "";
            }
              $stmt = $this->conn->prepare("SELECT log.NO,group_concat(ord.ORDER_NO),log.WTID, log.RESULT_CD, log.CUST_ID,ct.BUSINESS_NAME, log.URL_INFO, log.PAYMENT_TYPE, log.PARAMETER_INFO, log.PAYMENT_TM
                FROM TB_PAYMENT_LOG log join TB_CUST ct on log.CUST_ID = ct.CUST_ID
                left join (SELECT * from TB_ORDER where wtid IS NOT NULL && wtid != '') ord on ord.wtid = log.WTID and ord.cust_id = log.CUST_ID
                $days
                group by log.no
                ORDER BY log.NO  DESC $limit");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //결제로그 상세페이지
          public function admin_order_card_detail($no)
          {
              $stmt = $this->conn->prepare("SELECT log.NO,log.WTID,log.RESULT_CD,log.CUST_ID,URL_INFO,
                log.PAYMENT_TYPE,log.PARAMETER_INFO,log.PAYMENT_TM,ct.business_name
                FROM TB_PAYMENT_LOG log join TB_CUST ct on log.CUST_ID = ct.CUST_ID
                where no = '$no'");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //콘텐츠 리스트//공통제외
          public function select_content_list_group()
          {
              $stmt = $this->conn->prepare("SELECT CONTENT_TYPE FROM TB_CONTENT where CONTENT_TYPE != '공통'
                group by CONTENT_TYPE order by CONTENT_TYPE desc");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //콘텐츠 종류
          public function select_content_list($area)
          {

              if(is_int($area)) {
               // echo "This is number type";
               $where = "where CONTENT_NO = '$area'";
             }else {
               // echo "This is text type";
               $where = "where CONTENT_TYPE = '$area'";
             }
              $stmt = $this->conn->prepare("SELECT CONTENT_NO, CONTENT_TYPE, CONTENT_TITLE, CONTENT_INFO FROM TB_CONTENT
              $where");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //콘텐츠 정보
          public function select_content_info($rqt_content_no,$info_no)
          {
            if (isset($info_no)) {
              $info_no_where = " and INFO_NO = '$info_no' ";
            }
              $stmt = $this->conn->prepare("SELECT INFO_NO,CONTENT_NO, AREA,CONTENT_INFO_EPISODE,CONTENT_INFO_TITLE,
                CONTENT_IMG_TITLE, CONTENT_IMG_FIRST, CONTENT_IMG_SECOND, CONTENT_IMG_THIRD,CONTENT_INFO_CONT FROM TB_CONTENT_INFO
                where CONTENT_NO = '$rqt_content_no' $info_no_where");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //콘텐츠 정보 상세
          public function select_content_info_detail($rqt_content_no,$info_no)
          {
            if (isset($info_no)) {
              $info_no_where = " and info.INFO_NO = '$info_no' ";
            }
              $stmt = $this->conn->prepare("SELECT info.INFO_NO,info.CONTENT_NO, info.AREA,
                info.CONTENT_INFO_EPISODE,info.CONTENT_INFO_TITLE,
                info.CONTENT_IMG_TITLE, info.CONTENT_IMG_FIRST, info.CONTENT_IMG_SECOND,
                info.CONTENT_IMG_THIRD,conten.CONTENT_TITLE,info.CONTENT_INFO_CONT FROM TB_CONTENT_INFO info
                left join TB_CONTENT conten on info.CONTENT_NO = conten.CONTENT_NO
                where info.CONTENT_NO = '$rqt_content_no' $info_no_where");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //콘텐츠 정보 추가
          public function insert_content_info($AREA,$CONTENT_INFO_EPISODE,$CONTENT_INFO_TITLE,$CONTENT_IMG_TITLE,$CONTENT_IMG_FIRST,$CONTENT_IMG_SECOND,$CONTENT_IMG_THIRD)
          {
            //지역/에피소드/큰제목/메인이미지제목/썸네일/메인이미지/더보기이미지
            $stmt = $this->conn->prepare("INSERT INTO TB_CONTENT_INFO (AREA,CONTENT_INFO_EPISODE,CONTENT_INFO_TITLE,
              CONTENT_IMG_TITLE, CONTENT_IMG_FIRST, CONTENT_IMG_SECOND, CONTENT_IMG_THIRD)
              VALUES ('$AREA','$CONTENT_INFO_EPISODE','$CONTENT_INFO_TITLE',
                '$CONTENT_IMG_TITLE','$CONTENT_IMG_FIRST','$CONTENT_IMG_SECOND','$CONTENT_IMG_THIRD');");
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
            } else {
                return INSERT_FAILED;
            }
          }

          //콘텐츠 정보 업데이트
          public function update_content_info($request)
          {
            $content_no_one = $request["content_no_one"];//콘텐츠 번호
            $info_no = $request["info_no"];//콘텐츠 정보 번포

            $CONTENT_INFO_EPISODE = $request["CONTENT_INFO_EPISODE"];//에피소드
            $CONTENT_INFO_TITLE = $request["CONTENT_INFO_TITLE"];//큰제목
            $CONTENT_IMG_TITLE = $request["CONTENT_IMG_TITLE"];//메인 이미지제목
            $CONTENT_IMG_FIRST = $request["CONTENT_IMG_FIRST"];//썸네일 이미지
            $CONTENT_IMG_SECOND = $request["CONTENT_IMG_SECOND"];//메인 이미지
            $CONTENT_IMG_THIRD = $request["CONTENT_IMG_THIRD"];//메인 이미지
            $CONTENT_INFO_CONT = $request["CONTENT_INFO_CONT"];//정보내용
            // $CONTENT_IMG_FIRST = urlencode($request["CONTENT_IMG_FIRST"]);//썸네일 이미지
            // $CONTENT_IMG_SECOND = urlencode($request["CONTENT_IMG_SECOND"]);//메인 이미지
            // $CONTENT_IMG_THIRD = urlencode($request["CONTENT_IMG_THIRD"]);//메인 이미지

            //이미지없으면 업로드안함//
            if (isset($CONTENT_IMG_FIRST)) {
              $CONTENT_IMG_FIRST_WE = ",CONTENT_IMG_FIRST = '$CONTENT_IMG_FIRST'";//더보기 이미지
            }else {
              $CONTENT_IMG_FIRST_WE = "";
            }
            if (isset($CONTENT_IMG_SECOND)) {
              $CONTENT_IMG_SECOND_WE = ",CONTENT_IMG_SECOND = '$CONTENT_IMG_SECOND'";//더보기 이미지
            }else {
              $CONTENT_IMG_SECOND_WE = "";
            }
            if (isset($CONTENT_IMG_THIRD)) {
              $CONTENT_IMG_THIRD_WE = ",CONTENT_IMG_THIRD = '$CONTENT_IMG_THIRD'";//더보기 이미지
            }else {
              $CONTENT_IMG_THIRD_WE = "";
            }
            if (isset($CONTENT_INFO_CONT)) {
              $CONTENT_INFO_CONT_WE = ",CONTENT_INFO_CONT = '$CONTENT_INFO_CONT'";//더보기 이미지
            }else {
              $CONTENT_INFO_CONT_WE = "";
            }
            //이미지없으면 업로드안함//


            $str = "UPDATE TB_CONTENT_INFO SET
              CONTENT_INFO_EPISODE = '$CONTENT_INFO_EPISODE',
              CONTENT_INFO_TITLE = '$CONTENT_INFO_TITLE',
              CONTENT_IMG_TITLE = '$CONTENT_IMG_TITLE'
              $CONTENT_IMG_FIRST_WE
              $CONTENT_IMG_SECOND_WE
              $CONTENT_IMG_THIRD_WE
              $CONTENT_INFO_CONT_WE
              WHERE CONTENT_NO = '$content_no_one' and INFO_NO = '$info_no'";

            $stmt = $this->conn->prepare("$str");
            // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
            $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
                return UPDATE_COMPLETED;
            } else {
                return UPDATE_FAILED;
            }
          }

          public function select_random_ctg()
          {
              $stmt = $this->conn->prepare("SELECT CONTENT_CTG_NO,CONTENT_CTG_NAME FROM TB_CONTENT_CTG where CONTENT_CTG_USE_YN = 'Y' order by rand() LIMIT 1");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          public function select_content_ctg()
          {
              $stmt = $this->conn->prepare("SELECT CONTENT_CTG_NO,CONTENT_CTG_NAME,CONTENT_CTG_USE_YN FROM TB_CONTENT_CTG");
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  return $stmt;
              } else {
                  return SELECT_FAILED;
              }
          }

          //콘텐츠업종 업데이트
          public function update_content_ctg($request)
          {
            $CTG_NO = $request["ctg_no"];//콘텐츠 번호
            $USE_YN = $request["check"];//콘텐츠 정보 번포

            $str = "UPDATE TB_CONTENT_CTG SET CONTENT_CTG_USE_YN = '$USE_YN'
              WHERE CONTENT_CTG_NO = '$CTG_NO'";

            $stmt = $this->conn->prepare("$str");
            // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
            $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
                return UPDATE_COMPLETED;
                // return print_r($request,true);
            } else {
                return UPDATE_FAILED;
            }
          }
          //콘텐츠업종 추가
          public function insert_content_ctg($request)
          {
            $CTG_NAME = $request["ctg_name"];//콘텐츠 번호

            $str = "INSERT INTO TB_CONTENT_CTG(CONTENT_CTG_NAME,CONTENT_CTG_USE_YN) VALUES ('$CTG_NAME','Y')";

            $stmt = $this->conn->prepare("$str");
            // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
            $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
                return INSERT_COMPLETED;
                // return print_r($request,true);
            } else {
                return INSERT_FAILED;
            }
          }
          //콘텐츠업종 삭제
          public function delete_content_ctg($request)
          {
            $CTG_NO = $request["ctg_no"];//콘텐츠 번호

            $str = "DELETE FROM TB_CONTENT_CTG WHERE CONTENT_CTG_NO = $CTG_NO";

            $stmt = $this->conn->prepare("$str");
            // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
            $g = mysqli_error($this->conn);//에러메세지출력
            if ($stmt->execute()) {
                return DELETE_COMPLETED;
                // return print_r($request,true);
            } else {
                return DELETE_FAILED;
            }
          }

          //특가상품 추가..
          public function insert_specialProd($request)
          {
            $seller_prod_cd = $request["seller_prod_cd"];//유통사상품코드--스페셜 MS코드
            $seller_id = $request["seller_id"];//유통사아이디
            $seller_prod_name = $request["seller_prod_name"];//유통사상품명
            $seller_prod_price = $request["seller_prod_price"];//유통사상품가격
            $order_deadline_tm = $request["order_deadline_tm"];//유통사상품 D-DAY
            $max_order_am = $request["max_order_am"];//최대 발주수량

            $str = "INSERT INTO TB_SELLER_PROD_PRICE(SELLER_PROD_CD,SELLER_ID,SELLER_PROD_NAME,SELLER_PROD_PRICE,ORDER_DEADLINE_TM)
             VALUES ('$seller_prod_cd','$seller_id','$seller_prod_name',$seller_prod_price,$order_deadline_tm)";

             // INSERT INTO TB_SELLER_PROD_PRICE(SELLER_PROD_CD,SELLER_ID,SELLER_PROD_NAME,SELLER_PROD_PRICE,ORDER_DEADLINE_TM)
             // VALUES ("E0000011",'eventstore1','요플레(딸기),1kg,빙그래',5000,1);

            $complete = "INSERT INTO TB_SPECIAL_PROD (PROD_CD, SELLER_ID, MAX_ORDER_AM, ACTIV_YN)
            VALUES ('$seller_prod_cd','$seller_id',$max_order_am,'N')";

            $complete_final = "INSERT INTO TB_SELLER_PROD_CD (PROD_CD, SELLER_ID, SELLER_PROD_CD)
            VALUES ('$seller_prod_cd','$seller_id','$seller_prod_cd')";

            $stmt = $this->conn->prepare("$str");
            if ($stmt->execute()) {//상품추가성공
              $stmtComplete = $this->conn->prepare("$complete");
              if ($stmtComplete->execute()) {//스페셜 리스트 추가 성공
                $stmtCompleteFinal = $this->conn->prepare("$complete_final");
                if ($stmtCompleteFinal->execute()) {//매칭 하기
                  return INSERT_COMPLETED;
                }else {
                  return INSERT_FAILED;
                }
              }else {
                return INSERT_FAILED;
              }
            } else {
                return INSERT_FAILED;
            }
          }


          
}
// $ee = new DbOperation;
?>
