<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
class DbOperationDELIVERY
{
  private $conn;

  //Constructor
  function __construct()
  {
    require_once dirname(__FILE__) . '/config.php';
    require_once dirname(__FILE__) . '/DbConnect.php';

    // opening db connection
    $db = new DbConnect();
    $this->conn = $db->connect();

    // $this->serviceList = "성수동1가|성수동2가|사근동|행당동|송정동|화양동|자양동|군자동";
    // $this->JinhyunFarm = "";
  }

  public function UserList_All($search_textfield, $orderby, $admin_id, $admin_type, $none_cust, $s_point, $list, $center,$group,$dvClassCd)
  {
    if (isset($s_point) && isset($list)) {
      $limit = "limit $s_point,$list";
    } else {
      $limit = "";
    }
    //지역거점만 해당함
    $center_where = "AND cust.DELIV_POSITION REGEXP ('$this->serviceList') ";

    //TF 전용검색필터추가!
    if ($center == "ALL") {//전체업장

    }else if($center == "none"){//센터 미배정
      $center_where .= " and sqc.CENTER_CD is null || sqc.CENTER_CD ='' ";
    }else {//센터검색
      $center_where .= " and sqc.CENTER_CD like '$center'";
    }

    //TF 전용검색필터추가!2222
    if($group == "ALL"){//그룹 미지정
      $group_where .= "";
    }else if ($group == "none") {
      $group_where .= " and sqc.DELIV_GROUP_CD like ''";
    }else {//센터검색
      $group_where .= " and sqc.DELIV_GROUP_CD like '$group'";
    }
    // and devClass.DELIV_RANKING like '1'
    if (isset($dvClassCd) && $dvClassCd !== "") {
      $devClassWhere = " and devClass.DELIV_CLASS_CD like '$dvClassCd'";
    }else {
      $devClassWhere = "";
    }

    // echo "$search_textfield";
    if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
      $search_textfield_where = "where cust.activ_yn = 1";
      if ($none_cust == "" || !isset($none_cust) || empty($none_cust)) {
      } else {
        $search_textfield_where = "where cust.activ_yn = 0";
      }
    } else {
      $search_textfield_where = "where (cust.business_name like concat('%',?,'%') or cust.cust_id like concat('%',?,'%') or cust.owner_name like concat('%',?,'%')) and cust.activ_yn = 1";
      if ($none_cust == "" || !isset($none_cust) || empty($none_cust)) {
      } else {
        $search_textfield_where = "where (cust.business_name like concat('%',?,'%') or cust.cust_id like concat('%',?,'%') or cust.owner_name like concat('%',?,'%')) and cust.activ_yn = 0";
      }
    }

    $orderby_show = "order by  cust.reg_date desc,cust.cust_id";


    if ($admin_type == "MASTER" || $admin_type == "MANAGER" || $admin_type == "MD" || $admin_type == "DELIVRY") {
      if ($admin_id == "" || !isset($admin_id) || empty($admin_id) || $admin_id == "ALL") {
        $where = "";
      } elseif ($admin_id == "NONE") {
        $where = " and (cust.admin_id = '' or cust.admin_id is null)";
      } else {
        $where = " and cust.admin_id = '$admin_id'";
      }
    } else {
      $where = " and cust.admin_id = '$admin_id'";
    }

    $stmt = $this->conn->prepare("SELECT cust.cust_id,
       cust.business_name,
       cust.owner_name,
       cust.addr_cd,
       cust.addr_cont,
       cust.tel_no,
       cust.activ_yn,
       IF(Isnull(sel_cust.count_cust), 0, sel_cust.count_cust) as metSel,
       cust_pay.deposit_bln,
       cust_pay.credit_limit,
       cust.admin_id,
       gcd.GRADE_CLASS_NAME,
       cust.deliv_position,
       sqc.CENTER_CD as sqcCenterCd,
       sqc.DELIV_GROUP_CD as sqcGroupCd,
       sqc.DELIV_SQC as sqc,
       sqc.NO as sqcNO,
       devClass.DELIV_CONT,
       devClass.DELIV_CLASS_NO
FROM   (SELECT joincust.cust_id,
               joincust.password,
               joincust.business_name,
               joincust.owner_name,
               joincust.addr_cd,
               joincust.addr_cont,
               joincust.tel_no,
               joincust.activ_yn,
               joincust.ad_aggr_yn,
               joincust.reg_date,
               joincust.recommender_tel_no,
               joincust.recommender_code,
               joincust.admin_id,
               joincust.grade_class_cd,
               joincust.deliv_position,
               joincust.AML_GRADE_CLASS_CD,
               joincust.DELIV_CLASS_NO
        FROM   TB_CUST joincust
               left join TB_SELLER sel
                      ON joincust.cust_id = sel.seller_id
        WHERE  sel.seller_id IS NULL) cust
       left join (SELECT Count(seller_id) AS count_cust,
                         cust_id,
                         seller_id
                  FROM   TB_SELLER_BY_CUST
                  GROUP  BY cust_id) sel_cust
              ON cust.cust_id = sel_cust.cust_id
       join TB_CUST_PAYMENT cust_pay
         ON cust.cust_id = cust_pay.cust_id
       join TB_POINT_AML_GRADE_CLASS_CD gcd
         ON cust.AML_GRADE_CLASS_CD = gcd.GRADE_CLASS_CD
       left join TB_DELIV_SQC as sqc
         ON cust.cust_id = sqc.cust_id
       left join TB_DELIV_EXPECT_TM_CLASS as devClass
         ON cust.DELIV_CLASS_NO = devClass.DELIV_CLASS_NO
          $search_textfield_where $where $center_where $group_where $devClassWhere $orderby_show $limit");
    if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
    } else {
      $stmt->bind_param("sss", $search_textfield, $search_textfield, $search_textfield);
    }
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }
  public function selectCenterCd($REQ)
  {
    $stmt = $this->conn->prepare("SELECT CENTER_CD, CENTER_NAME FROM TB_CENTER_CD");
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }
  public function selecPack($REQ)
  {
    $stmt = $this->conn->prepare("SELECT PACKING_NO, PACKING_CLASS_NAME FROM TB_PACKING");
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }

  public function selectDvGroupCd($REQ)
  {
    $sqcCenterCd = $REQ["sqcCenterCd"];

    $stmt = $this->conn->prepare("SELECT DELIV_GROUP_CD, DELIV_GROUP_NAME, DELIV_GROUP_CONT, CENTER_CD, ADMIN_ID, USE_YN
       FROM TB_DELIV_GROUP_CD
       WHERE CENTER_CD = '$sqcCenterCd'");
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }


  public function selectDvSQC($REQ)
  {

    $sqcCenterCd = $REQ["sqcCenterCd"];
    $sqcGroupCd = $REQ["sqcGroupCd"];

    $stmt = $this->conn->prepare("SELECT NO, CENTER_CD, DELIV_GROUP_CD, DELIV_SQC, CUST_ID
       FROM TB_DELIV_SQC
       WHERE CENTER_CD = '$sqcCenterCd'
       AND DELIV_GROUP_CD = '$sqcGroupCd'
       ORDER BY DELIV_SQC ASC");
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }

  //
  private function isDvSqcExist($REQ)
  {
      $CUST_ID = $REQ["CUST_ID"];//고객아이디
      $stmt = $this->conn->prepare("SELECT cust_id FROM TB_DELIV_SQC WHERE cust_id = '$CUST_ID'");
      $stmt->execute();
      $stmt->store_result();
      return $stmt->num_rows > 0;
  }
  //센터정보 입력
  public function InsertDvSQC($request)
  {
    // $DELIV_GROUP_CD = $request["DELIV_GROUP_CD"];//배송그룹
    // $DELIV_SQC = $request["DELIV_SQC"];//배송순서
    // $BEFOR_SQC = $request["BEFOR_SQC"];//변경전 순서
    // $AFTER_SQC = $request["AFTER_SQC"];//변경후 순서
    // $AFTER_SQC = $request["InUp"];//수정 삭제여부 .. IN : 추가 / UP : 수정
    // $NO = $request["NO"];//시퀀스번호
    $CENTER_CD = $request["CENTER_CD"];//센터코드
    $CUST_ID = $request["CUST_ID"];//고객아이디
    if (!$this->isDvSqcExist($request)) {
      $str = "INSERT INTO TB_DELIV_SQC(CENTER_CD, DELIV_GROUP_CD, DELIV_SQC, CUST_ID)
      -- SELECT '$CENTER_CD',IFNULL(deGroup.DELIV_GROUP_CD,''), IFNULL(MAX(sqc.DELIV_SQC)+1,1),'$CUST_ID'
      SELECT '$CENTER_CD','','','$CUST_ID'
      FROM TB_DELIV_GROUP_CD as deGroup
      LEFT JOIN TB_DELIV_SQC as sqc
      on sqc.CENTER_CD = deGroup.CENTER_CD
      WHERE deGroup.CENTER_CD = '$CENTER_CD' limit 1";

      $stmt = $this->conn->prepare("$str");
      // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
      $g = mysqli_error($this->conn);//에러메세지출력
      if ($stmt->execute()) {
        return INSERT_COMPLETED;
      } else {
        return INSERT_FAILED;
      }
    }else {
      return INSERT_FAILED;
    }

  }
  //센터정보 입력 수정
  public function UpdateDvSQC($request)
  {
    $UpType = $request["UpType"];//업데이트구분 ALL : 전체 / ONE : 컬럼별

    $NO = $request["NO"];//시퀀스번호
    $COLUMN = $request["COLUMN"];//컬럼명
    $COLUMN_VAL = $request["COLUMN_VAL"];//바꿀데이터
    $addUpdate = "";

    //센터코드 수정시 그룹코드는 초기화되고 인덱스는 새로 추가됨.
    // if ($COLUMN == "CENTER_CD") {
    //
    //   $addUpdate .= ",DELIV_GROUP_CD = (SELECT * FROM";
    //   $addUpdate .= " (SELECT IFNULL(deGroup.DELIV_GROUP_CD,'')
    //   FROM TB_DELIV_GROUP_CD as deGroup
    //   LEFT JOIN TB_DELIV_SQC as sqc
    //   on sqc.CENTER_CD = deGroup.CENTER_CD
    //   WHERE deGroup.CENTER_CD = '$COLUMN_VAL'
    //   AND sqc.DELIV_GROUP_CD = deGroup.DELIV_GROUP_CD limit 1) as SQC)";
    //
    //   $addUpdate .= ",DELIV_SQC = (SELECT * FROM";
    //   $addUpdate .= " (SELECT IFNULL(MAX(sqc.DELIV_SQC)+1,1)
    //   FROM TB_DELIV_GROUP_CD as deGroup
    //   LEFT JOIN TB_DELIV_SQC as sqc
    //   on sqc.CENTER_CD = deGroup.CENTER_CD
    //   WHERE deGroup.CENTER_CD = '$COLUMN_VAL'
    //   AND sqc.DELIV_GROUP_CD = deGroup.DELIV_GROUP_CD limit 1) as SQC)";
    //
    // }elseif ($COLUMN == "DELIV_GROUP_CD") {
      // $addUpdate .= ",DELIV_SQC = (SELECT * FROM";
      // $addUpdate .= " (SELECT IFNULL(MAX(sqc.DELIV_SQC)+1,1)
      // FROM TB_DELIV_GROUP_CD as deGroup
      // LEFT JOIN TB_DELIV_SQC as sqc
      // on sqc.CENTER_CD = deGroup.CENTER_CD
      // WHERE deGroup.DELIV_GROUP_CD = '$COLUMN_VAL'
      // AND sqc.DELIV_GROUP_CD = deGroup.DELIV_GROUP_CD limit 1) as SQC)";
      $addUpdate .= ",DELIV_SQC = 0";
    // }

    // $DELIV_GROUP_CD = $request["DELIV_GROUP_CD"];//배송그룹
    // $DELIV_SQC = $request["DELIV_SQC"];//배송순서
    // $BEFOR_SQC = $request["BEFOR_SQC"];//변경전 순서
    // $AFTER_SQC = $request["AFTER_SQC"];//변경후 순서
    // $AFTER_SQC = $request["InUp"];//수정 삭제여부 .. IN : 추가 / UP : 수정
    // $CENTER_CD = $request["CENTER_CD"];//센터코드
    // $CUST_ID = $request["CUST_ID"];//고객아이디

    $str = "UPDATE TB_DELIV_SQC
    SET $COLUMN='$COLUMN_VAL' $addUpdate
    WHERE NO ='$NO'";

    $stmt = $this->conn->prepare("$str");
    // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
    $g = mysqli_error($this->conn);//에러메세지출력
    if ($stmt->execute()) {
      return UPDATE_COMPLETED;
    } else {
      return UPDATE_FAILED;
    }
  }

  // 순번 테이블 조회
  public function selectCustDeliverySqc($REQ)
  {
    $sqcCenterCd = $REQ["sqcCenterCd"];
    $sqcGroupCd = $REQ["sqcGroupCd"];
    $dvClassCd = $REQ["dvClassCd"];
    if (isset($REQ["s_point"]) && isset($REQ["list"])) {
      $limit = "limit ".$REQ["s_point"].",".$REQ["list"]."";
    }else {
      $limit = "";
    }


    if ($sqcCenterCd == "") {
      $whereJoin = "|| (sqc.CENTER_CD IS NULL
                    AND sqc.DELIV_GROUP_CD IS NULL
                    AND sqc.DELIV_SQC IS NULL)";
    }else {
      $whereJoin = "";
    }

    // and devClass.DELIV_RANKING like '1'
    if (isset($REQ["dvClassCd"]) && $REQ["dvClassCd"] !== "") {
      $devClassWhere = " AND detc.DELIV_CLASS_CD like '$dvClassCd'";
    }else {
      $devClassWhere = "";
    }

    $str = "SELECT
    sqc.CENTER_CD,
    center.CENTER_NAME,
    sqc.DELIV_GROUP_CD,
    degc.DELIV_GROUP_NAME,
    cust.CUST_ID,
    cust.BUSINESS_NAME,
    detc.DELIV_CONT,
    sqc.DELIV_SQC
    FROM
        TB_CUST cust
    JOIN TB_DELIV_EXPECT_TM_CLASS detc ON
        cust.DELIV_CLASS_NO = detc.DELIV_CLASS_NO
    LEFT JOIN TB_DELIV_SQC sqc ON
        cust.cust_id = sqc.cust_id
    LEFT JOIN TB_CENTER_CD center ON
        sqc.CENTER_CD = center.CENTER_CD
    LEFT JOIN TB_DELIV_GROUP_CD degc ON
        sqc.CENTER_CD = degc.CENTER_CD AND sqc.DELIV_GROUP_CD = degc.DELIV_GROUP_CD
    WHERE
        cust.activ_yn = 1
        AND sqc.CENTER_CD = '$sqcCenterCd'
        AND sqc.DELIV_GROUP_CD = '$sqcGroupCd'
        $whereJoin
        $devClassWhere

    ORDER BY sqc.CENTER_CD DESC,sqc.DELIV_SQC asc $limit";
    $stmt = $this->conn->prepare("$str");

    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }

  //순번업데이트
  public function UpdateExcelSQC($request)
  {
    $DELIV_SQC = $request["DELIV_SQC"];//사용자
    $CUST_ID = $request["CUST_ID"];//사용자
    $CENTER_CD = $request["CENTER_CD"];//센터코드
    $DELIV_GROUP_CD = $request["DELIV_GROUP_CD"];//그룹코드

    $str = "UPDATE TB_DELIV_SQC SET DELIV_SQC = '$DELIV_SQC'
      WHERE CUST_ID = '$CUST_ID'
      AND CENTER_CD = '$CENTER_CD'
      AND DELIV_GROUP_CD = '$DELIV_GROUP_CD'";

    $stmt = $this->conn->prepare("$str");
    // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
    $g = mysqli_error($this->conn);//에러메세지출력
    if ($stmt->execute()) {
      return $str;
    } else {
      return UPDATE_FAILED;
    }
  }
  // 배송 희망 시간 데이터 불러오기
  public function DeliveExpect($REQ)
  {
    $DELIV_CLASS_NO = $REQ["DELIV_CLASS_NO"];
    $deliv_position = $REQ["deliv_position"];
    if (isset($DELIV_CLASS_NO)) {
      $where = "WHERE DELIV_POSITION = (
        SELECT DELIV_POSITION
        FROM TB_DELIV_EXPECT_TM_CLASS
        WHERE DELIV_CLASS_NO = '$DELIV_CLASS_NO')";
    }else {
      $where = "WHERE DELIV_POSITION = '$deliv_position'";
    }

    $str = "SELECT DELIV_CLASS_NO, DELIV_POSITION, DELIV_CLASS_CD, DELIV_RANKING, START_TM, END_TM, AVG_TM, DELIV_CONT
    FROM TB_DELIV_EXPECT_TM_CLASS
    $where";
    $stmt = $this->conn->prepare("$str");

    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }




  //센터정보 입력 및 수정
  public function InsertUpdateCenterInfo($request)
  {

    $NO = $request["NO"];//시퀀스번호
    $CENTER_CD = $request["CENTER_CD"];//센터코드
    $DELIV_GROUP_CD = $request["DELIV_GROUP_CD"];//배송그룹
    $DELIV_SQC = $request["DELIV_SQC"];//배송순서
    $CUST_ID = $request["CUST_ID"];//고객아이디
    $BEFOR_SQC = $request["BEFOR_SQC"];//변경전 순서
    $AFTER_SQC = $request["AFTER_SQC"];//변경후 순서
    $AFTER_SQC = $request["InUp"];//수정 삭제여부 .. IN : 추가 / UP : 수정

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

  //식당별 거래명세서
  public function custProdAllList($REQ,$groupYN) {

    $order_cond_cd = $REQ["order_cond_cd"];
    $cust_id = $REQ["cust_id"];
    $searchDate = $REQ["searchDate"];
    $center_cd = $REQ["center_cd"];
    $group_cd = $REQ["group_cd"];
    $packingNo = $REQ["packingNo"];
    $dvClassCd = $REQ["dvClassCd"];
    $search_textfield = $REQ["search_textfield"];
    $sdg_where = "AND cust.DELIV_POSITION REGEXP ('$this->serviceList') ";

    //TF 전용검색필터추가!
    $center_where="";
    if ($center_cd == "ALL") {//전체업장

    }else if($center_cd == "none"){//센터 미배정
      $center_where .= " AND sqc.CENTER_CD is null || sqc.CENTER_CD ='' ";
    }else {//센터검색
      $center_where .= " AND sqc.CENTER_CD like '$center_cd'";
    }

    //TF 전용검색필터추가!2222
    $group_where="";
    if($group_cd == "ALL"){//그룹 미지정
      $group_where .= "";
    }else if ($group_cd == "none") {
      $group_where .= " AND sqc.DELIV_GROUP_CD like ''";
    }else {//센터검색
      $group_where .= " AND sqc.DELIV_GROUP_CD like '$group_cd'";
    }
    $packingNoWhere ="";
    if (strlen($packingNo) > 0) {
      $packingNoWhere .= " AND prod.PACKING_NO like '$packingNo'";
    }

    // and devClass.DELIV_RANKING like '1'
    if (isset($dvClassCd) && $dvClassCd !== "") {
      $devClassWhere = " AND delCd.DELIV_CLASS_CD like '$dvClassCd'";
    }else {
      $devClassWhere = "";
    }
    if ($groupYN == "N") {//기본
      //총주문금액
      $total_price_group = "sum(item.order_pay * item.PROD_ORDER_CNT) AS total_price,";
      //주문상품수
      $PROD_ORDER_CNT_group = "COUNT(item.PROD_ORDER_CNT) as PROD_ORDER_CNT,";
      //그룹핑..
      $grouping = "GROUP BY CUST_ID";
    }else {
      //총주문금액
      $total_price_group = "(item.order_pay *sum(item.PROD_ORDER_CNT)) AS total_price,";
      //주문상품수
      $PROD_ORDER_CNT_group = "sum(item.PROD_ORDER_CNT) as PROD_ORDER_CNT,";
      //그룹핑..
      $grouping = "GROUP BY PROD_CD,SELLER_ID";
    }

    if ($search_textfield == "" || !isset($search_textfield) || empty($search_textfield)) {
      $search_textfield_where = "";
      // $search_textfield_where = "where cust.activ_yn = 1";
    } else {
      $search_textfield_where = " AND cust.business_name like '%$search_textfield%' AND cust.activ_yn = 1 ";
      // or cust.cust_id like '%$cust_id%'
    }

    $stmt = $this->conn->prepare("SELECT item.ORDER_ITEM_NO,
                    item.ORDER_NO,
                    sellCd.SELLER_PROD_CD,
                    item.SELLER_ID,
                    seller.SELLER_NAME,
                    cust.TEL_NO,
                    cust.ADDR_CONT,
                    item.order_cond_cd,
                    item.PROD_CD,
                    prod.PROD_NAME,
                    prod.PROD_CONT,
                    prod.PROD_WGT,
                    prod.FACT_NAME,
                    $PROD_ORDER_CNT_group
                    item.order_costpr,
                    prod.TAXFREE_YN,
                    IF(prod.TAXFREE_YN = 0, (item.order_pay - item.order_costpr) , '')AS tax_pay,
                    $total_price_group
                  item.coupon_price,
                  (item.order_pay * sum(item.PROD_ORDER_CNT))-item.coupon_price AS pay_price,
                  IF(item.SELLER_ID != 'deliverylab',CONCAT('D-',prod.ORDER_DEADLINE_TM), '택배상품') as dead_tm,
                    cust.BUSINESS_NAME,
                    cust.CUST_ID,
                    cust.DELIV_POSITION,
                    item.ARRIVE_DATE,
                    prod.STN_COND_CD,
                    delCd.DELIV_RANKING,
                    delCd.DELIV_CONT,
                    sqc.CENTER_CD,
                    center.CENTER_NAME,
                    sqc.DELIV_GROUP_CD,
                    sqc.DELIV_SQC,
                    pack.PACKING_CLASS_NAME,
                    item.PICKING_YN,
                    item.ITEM_MEMO
                    FROM TB_ORDER_ITEM item
                    LEFT OUTER JOIN TB_SELLER seller ON item.SELLER_ID = seller.SELLER_ID
                    LEFT OUTER JOIN TB_ORDER od ON od.ORDER_NO = item.ORDER_NO
                    LEFT OUTER JOIN TB_PROD prod ON prod.PROD_CD = item.PROD_CD
                    LEFT OUTER JOIN TB_CUST cust ON cust.CUST_ID = od.CUST_ID
                    LEFT OUTER JOIN TB_SELLER_PROD_CD sellCd ON sellCd.SELLER_ID = item.SELLER_ID AND sellCd.PROD_CD = item.PROD_CD
                    LEFT OUTER JOIN TB_DELIV_EXPECT_TM_CLASS delCd ON cust.DELIV_CLASS_NO = delCd.DELIV_CLASS_NO
                    LEFT OUTER JOIN TB_DELIV_SQC sqc ON cust.CUST_ID = sqc.CUST_ID
                    LEFT OUTER JOIN TB_CENTER_CD center ON sqc.CENTER_CD = center.CENTER_CD
                    LEFT OUTER JOIN TB_PACKING pack ON prod.PACKING_NO = pack.PACKING_NO
                    WHERE item.order_cond_cd = ?
                       AND cust.cust_id LIKE '$cust_id'
                       AND item.ARRIVE_DATE = ?
                       $center_where
                       $group_where
                       $packingNoWhere
                       $sdg_where
                       $devClassWhere
                       $search_textfield_where
                       $grouping
                    ORDER BY item.ARRIVE_DATE ASC , seller.SELLER_NAME ASC, prod.PROD_NAME ASC");
    $stmt->bind_param("ss", $order_cond_cd,$searchDate);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return $stmt;
    } else {
        return SELECT_FAILED;
    }
  }

  public function custDateAllList($order_cond_cd,$searchDate,$center_cd,$group_cd,$searchDelivtime,$cust_id) {

    $cust_idStr = "";
    if ($cust_id != "") {
      $cust_idStr = " AND cust.CUST_ID = ".$cust_id;
    }

    $sdg_where = "AND cust.DELIV_POSITION REGEXP ('$this->serviceList') ";

    $delTimeStr = "";
    if ($searchDelivtime != "") {
      $delTimeStr = " AND cust.DELIV_CLASS_NO = ".$searchDelivtime;
    }

    //TF 전용검색필터추가!
    $center_where="";
    if ($center_cd == "ALL") {//전체업장

    }else if($center_cd == "none"){//센터 미배정
      $center_where .= " and (sqc.CENTER_CD is null || sqc.CENTER_CD ='') ";
    }else {//센터검색
      $center_where .= " and sqc.CENTER_CD like '$center_cd'";
    }


    //TF 전용검색필터추가!2222
    $group_where="";
    if($group_cd == "ALL"){//그룹 미지정
      $group_where .= "";
    }else if ($group_cd == "none") {
      $group_where .= " and sqc.DELIV_GROUP_CD like ''";
    }else {//센터검색
      $group_where .= " and sqc.DELIV_GROUP_CD like '$group_cd'";
    }


    $stmt = $this->conn->prepare("SELECT   item.ORDER_NO,
                                          cust.cust_id,
                                          cust.BUSINESS_NAME,
                                          cust.DELIV_POSITION,
                                          od.ORDER_DATE,
                                          cust.CARRY_INFO
                    FROM TB_ORDER_ITEM item
                    LEFT OUTER JOIN TB_ORDER od ON od.ORDER_NO = item.ORDER_NO
                    LEFT OUTER JOIN TB_CUST cust ON cust.CUST_ID = od.CUST_ID
                    LEFT OUTER JOIN TB_DELIV_SQC sqc ON cust.CUST_ID = sqc.CUST_ID
                    LEFT OUTER JOIN TB_DELIV_EXPECT_TM_CLASS etc ON cust.DELIV_CLASS_NO = etc.DELIV_CLASS_NO
                    WHERE item.order_cond_cd = ?
                       AND item.ARRIVE_DATE = ?
                       $center_where
                       $group_where
                       $delTimeStr
                       $sdg_where
                       $cust_idStr
                     GROUP BY cust.cust_id ASC
                     order by etc.DELIV_RANKING asc,sqc.DELIV_GROUP_CD,sqc.DELIV_SQC asc");
    $stmt->bind_param("ss", $order_cond_cd,$searchDate);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return $stmt;
    } else {
        return SELECT_FAILED;
    }
  }
  // 주문건의 요청사항.
  public function custOrderByMemoList($order_no) {
    $stmt = $this->conn->prepare("SELECT mo.order_no, seller.seller_name, mo.memo FROM TB_CUST_MEMO mo
                                  LEFT OUTER JOIN TB_SELLER seller ON mo.SELLER_ID = seller.SELLER_ID
                                   WHERE order_no = ?");
    $stmt->bind_param("i",$order_no);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
      return $stmt;
    }else{
      return SELECT_FAILED;
    }
  }

  // 지역 거점 전체 출력
  public function selectLocalPosition()
  {
    $query = "SELECT NO,
                     AREA,
                     AREA_CLASS

              FROM   TB_AREA_CLASS

              WHERE  AREA_CLASS_YN = 'Y'

              ORDER BY AREA, AREA_CLASS";

    $stmt = $this->conn->prepare($query);

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }

  // 2021-06-18 주문결제 / 반품 LIST
  public function orderPayList($date1, $date2, $searchText, $sel_id, $occd, $yn, $cust_id, $s_point, $list, $admin_type, $admin_id, $sales_select, $sdg_get, $arrive_yn = false)
  {
    if (isset($s_point) && isset($list)) {
      $limit = "limit $s_point, $list";
    } else {
      $limit = "";
    }

    if ($arrive_yn) {
      $date_format = "date(item.ARRIVE_DATE) BETWEEN '$date1' AND '$date2'";
    } else {
      $date_format = "date(ord.ORDER_DATE) BETWEEN '$date1' AND '$date2'";
    }

    if ($sel_id == "ALL") {
      $seller = "";
    } else {
      $seller = "AND item.SELLER_ID like '%$sel_id%'";
    }

    if ($cust_id == "ALL") {
      $cust = "";
    } else {
      $cust = "AND ord.CUST_ID like '%$cust_id%'";
    }

    if ($occd == "ALL") {
      $order_cond_cd = "";
    } else {
      $order_cond_cd = "AND item.order_cond_cd = $occd";
    }

    if ($arrive_yn) {
      if ($yn == 2) {
        $tx_yn = "GROUP BY item.ORDER_ITEM_NO ORDER BY item.ARRIVE_DATE ASC, item.ORDER_ITEM_NO DESC";
      } else {
        $tx_yn = "AND prd.TAXFREE_YN = $yn GROUP BY item.ORDER_ITEM_NO ORDER BY item.ARRIVE_DATE ASC, item.ORDER_ITEM_NO DESC ";
      }
    } else {
      if ($yn == 2) {
        $tx_yn = "GROUP BY item.ORDER_ITEM_NO ORDER BY ord.ORDER_DATE ASC, item.ORDER_ITEM_NO DESC";
      } else {
        $tx_yn = "AND prd.TAXFREE_YN = $yn GROUP BY item.ORDER_ITEM_NO ORDER BY ord.ORDER_DATE ASC, item.ORDER_ITEM_NO DESC ";
      }
    }

    if ($admin_type == "SALES") {
      $admin_type_join = " join (SELECT * FROM TB_ADMIN where admin_id = '$admin_id') admin on cust.admin_id = admin.admin_id";
    } else {
      $admin_type_join = "";
    }

    if ($sales_select == "ALL") {
      $sales_select_where = "";
    } elseif ($sales_select == "NONE") {
      $sales_select_where = " AND cust.admin_name is null ";
    } else {
      $sales_select_where = " AND cust.admin_id ='$sales_select' ";
    }

    // 정산관리 검색필터
    if ($sdg_get == "basic") {
      $sdg_get_where = " AND (cust.DELIV_POSITION NOT REGEXP ('$this->serviceList') or cust.DELIV_POSITION is null) ";
    } else if ($sdg_get == "ALL") {
      $sdg_get_where = "";
    } else if ($sdg_get == "sdg") {
      $sdg_get_where = " AND cust.DELIV_POSITION REGEXP ('$this->serviceList') ";
    } else if(strpos($sdg_get,"|") !== false) {
      $sdg_get_where = " AND cust.DELIV_POSITION REGEXP ('$sdg_get')";
    } else {
      $sdg_get_where = " AND cust.DELIV_POSITION like '$sdg_get' ";
    }

    // 식당명 검색
    if (!is_null($searchText) && $searchText != "") {
      $search = " AND cust.BUSINESS_NAME LIKE '%$searchText%'";
    } else {
      $search = "";
    }


    //TF 전용검색필터추가!
    $center_cd="ALL";
    $center_where="";
    if ($center_cd == "ALL") {//전체업장
      $center_where .= "";
    }else if($center_cd == "none"){//센터 미배정
      $center_where .= " and sqc.CENTER_CD is null || sqc.CENTER_CD ='' ";
    }else {//센터검색
      $center_where .= " and sqc.CENTER_CD like '$center_cd'";
    }


    //TF 전용검색필터추가!2222
    $group_cd="ALL";
    $group_where="";
    if($group_cd == "ALL"){//그룹 미지정
      $group_where .= "";
    }else if ($group_cd == "none") {
      $group_where .= " and sqc.DELIV_GROUP_CD like ''";
    }else {//센터검색
      $group_where .= " and sqc.DELIV_GROUP_CD like '$group_cd'";
    }

    $stmt = $this->conn->prepare("SELECT
                                    item.order_item_no 아이템번호,
                                    item.coupon_price 쿠폰할인액,
                                    ord.ORDER_DATE 주문날짜,
                                    ord.ORDER_NO 주문번호,
                                    item.SELLER_ID 유통아이디,
                                    sel.SELLER_NAME 유통사명,
                                    ord.CUST_ID 식당아이디,
                                    cust.BUSINESS_NAME 식당명,
                                    cust.OWNER_NAME 식당대표자명,
                                    ctg.ER_CTG_NAME 업종정보,             -- 2021-07-14 업종 정보 추가
                                    item.PROD_CD MS상품코드,
                                    item.SELLER_PROD_CD 유통사상품코드,
                                    prd.PROD_NAME 상품명,
                                    prd.PROD_CONT 상품내용,
                                    prd.PROD_WGT 상품중량,
                                    LEFT(prd.CLASS_CD, 1) 대분류,               -- 2021-07-20 대분류 추가
                                    prd.CLASS_CD 분류명,                        -- 2021-07-20 분류명 추가
                                    prd.CLASS_DETAIL_CD 분류상세코드,            -- 2021-07-20 분류상세코드 추가
                                    prd.FACT_NAME 생산지,
                                    prd.TAXFREE_YN 면세코드,
                                    if(prd.TAXFREE_YN='1','면세','과세') 면세여부,
                                    prd.STN_COND_CD 보관상태,
                                    tsc.STN_COND_NAME 보관상태명,
                                    item.ORDER_DEADLINE_TM 배송기간,
                                    item.order_cond_cd 주문상태코드,
                                    ord_cond_cd.ORDER_COND_NAME 주문상태,
                                    item.order_pay 상품금액,
                                    item.PROD_ORDER_CNT 주문수량,
                                    (item.order_pay * item.prod_order_cnt) 상품금액X주문수량,
                                    if(prd.TAXFREE_YN='1',item.order_pay,item.order_costpr) 공급가,
                                    if(prd.TAXFREE_YN='1',0,item.order_pay-item.order_costpr) 부가세,
                                    if(prd.TAXFREE_YN='1',(item.order_pay * item.prod_order_cnt),(item.order_costpr * item.prod_order_cnt)) 총공급가,
                                    if(prd.TAXFREE_YN='1',0,((item.order_pay-item.order_costpr) * item.prod_order_cnt)) 총부가세,
                                    if(ord.wtid is null or ord.wtid ='','예치금',if(INSTR(ord.wtid,'VBNK'),'계좌','카드')) 카드결제여부,
                                    coupon_his.COUPON_NO AS 쿠폰번호,
                                    coupon_his.COUPON_DISCOUNT_PRICE 쿠폰금액,
                                    cust.DELIV_POSITION AS 성수동,
                                    cust.admin_name AS 매칭영업사원,
                                    ord.wtid AS WTID,
                                    item.ARRIVE_DATE as 입고예정일,
                                    soi.SELLER_ORDER_NAME AS 매입처주문명,
                                    memo.memo,
                                    cust.DELIV_CLASS_NO,
                                    center.CENTER_NAME,
                                    sqc.CENTER_CD,
                                    degc.DELIV_GROUP_NAME,
                                    sqc.DELIV_GROUP_CD,
                                    sqc.DELIV_SQC,
                                    pack.PACKING_CLASS_NAME
                                  FROM
                                    TB_ORDER_ITEM item
                                    LEFT JOIN TB_COUPON_HIS coupon_his
                                      ON item.ORDER_NO = coupon_his.ORDER_NO AND item.SELLER_ID = coupon_his.SELLER_ID
                                    JOIN TB_PROD prd
                                      ON item.PROD_CD = prd.PROD_CD
                                    JOIN TB_STN_COND tsc
                                      ON prd.STN_COND_CD = tsc.STN_COND_CD
                                    JOIN TB_ORDER_COND_CD ord_cond_cd
                                      ON item.order_cond_cd = ord_cond_cd.ORDER_COND_CD
                                    JOIN TB_SELLER sel
                                      ON item.SELLER_ID = sel.SELLER_ID
                                    JOIN TB_ORDER ord
                                      ON ord.order_no = item.order_no
                                    INNER JOIN (SELECT ct.*, adn.admin_name FROM TB_CUST ct LEFT JOIN TB_ADMIN adn ON ct.admin_id = adn.admin_id) cust
                                      ON ord.cust_id = cust.cust_id
                                    JOIN TB_ER_CTG ctg                  -- 2021-07-14 업종 정보 JOIN 추가
                                      ON cust.CTG_CD = ctg.ER_CTG_CD
                                    LEFT JOIN TB_SELLER_ORDER_INFO soi
                                      ON ord.CUST_ID = soi.CUST_ID AND item.SELLER_ID = soi.SELLER_ID
                                    LEFT JOIN (SELECT ORDER_NO, SELLER_ID, memo FROM TB_CUST_MEMO WHERE memo IS NOT NULL AND memo != '') AS memo
                                      ON ord.ORDER_NO = memo.ORDER_NO AND item.SELLER_ID = memo.SELLER_ID
                                    LEFT OUTER JOIN TB_DELIV_SQC sqc ON cust.CUST_ID = sqc.CUST_ID
                                    LEFT OUTER JOIN TB_CENTER_CD center ON sqc.CENTER_CD = center.CENTER_CD
                                    LEFT OUTER JOIN TB_DELIV_GROUP_CD degc ON sqc.CENTER_CD = degc.CENTER_CD AND sqc.DELIV_GROUP_CD = degc.DELIV_GROUP_CD
                                    LEFT OUTER JOIN TB_DELIV_EXPECT_TM_CLASS etc ON cust.DELIV_CLASS_NO = etc.DELIV_CLASS_NO
                                    LEFT OUTER JOIN TB_PACKING pack ON prd.PACKING_NO = pack.PACKING_NO
                                    $admin_type_join
                                  WHERE
                                    $date_format $seller $cust $order_cond_cd $sales_select_where $sdg_get_where $search $tx_yn $limit");

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }

  public function selectMS_prod_cd_img($class, $class_cd, $class_cd_detail, $option, $search_textfield, $image, $s_point, $list)
  {
    // echo "$class,$class_cd,$class_cd_detail,$option,$search_textfield";
    $echo_text = explode(" ", $search_textfield);
    $echo_text_name = $echo_text[0];
    $echo_text_cont = $echo_text[1];
    $str = "SELECT prod.PROD_CD,
                   prod.CLASS_CD,
                   prod.CLASS_DETAIL_CD,
                   prod.PROD_NAME,
                   prod.PROD_CONT,
                   prod.PROD_WGT,
                   prod.SALE_UNIT,
                   prod.FACT_NAME,
                   prod.TAXFREE_YN,
                   prod.STN_COND_CD,
                   prod.ORDER_DEADLINE_TM,
                   prod.ORIGIN_NAME,
                   prod.REG_DATE,
                   prod.UPDATE_DATE,
                   prod.img,
                   pack.PACKING_CLASS_NAME,
                   prod.PACKING_NO

            FROM   TB_PROD prod ";

    $joinT = "";
    $joinT .= " LEFT OUTER JOIN TB_PACKING pack ON prod.PACKING_NO = pack.PACKING_NO ";

    $str .= $joinT;

    if ($option == "ALL") {
      $order_by = "prod.PROD_CD";
    } else {
      $order_by = "prod.PROD_NAME";
    }

    if (isset($s_point) && isset($list)) {
      $limit = "order by $order_by limit $s_point,$list";
    } else {
      $limit = "order by $order_by ";
    }

    if ($image == 'N') {
      $not_image = "and prod.img='notimg' ";
    } else {
      $not_image = "";
    }

    // echo "$echo_text_name / $echo_text_cont";
    if ($class == "ALL" && $search_textfield == "") {
      if ($image == 'N') {
        $not_image = "where prod.img='notimg' ";
      }
      // echo "전체 / 키워드X" / 이미지;
      $stmt = $this->conn->prepare("$str $not_image $limit");
    } else if ($class != "ALL" && $search_textfield == "") {
      // echo "분류 / 키워드X";
      if ($class_cd == "ALL") {
        // echo "/ 1차 전체";
        $stmt = $this->conn->prepare("$str
             where prod.PROD_CD like concat('%',?,'%') $not_image $limit");
        $stmt->bind_param("s", $class);
      } else {
        if ($class_cd_detail == "ALL") {
          // echo "/ 2차 전체";
          $stmt = $this->conn->prepare("$str
               where prod.PROD_CD like concat('%',?,'%') and prod.class_cd = ? $not_image $limit");
          $stmt->bind_param("ss", $class, $class_cd);
        } else {
          // echo "/ 2차 분류";
          $stmt = $this->conn->prepare("$str
               where prod.PROD_CD like concat('%',?,'%') and prod.class_cd = ? and prod.class_detail_cd = ? $not_image $limit");
          $stmt->bind_param("sss", $class, $class_cd, $class_cd_detail);
        }
      }
    } else if ($class == "ALL" && $search_textfield != "") {
      // echo "전체 / 키워드O";
      if ($echo_text_cont == "") {
        if ($option == "ALL") {
          // echo "string";
          $stmt = $this->conn->prepare("$str where
              (prod.prod_name like concat('%','$search_textfield','%')  or
              prod.prod_cd like concat('%','$search_textfield','%')  or
              prod.prod_cont like concat('%','$search_textfield','%')  or
              prod.prod_wgt like concat('%','$search_textfield','%')  or
              prod.fact_name like concat('%','$search_textfield','%'))
              $not_image $limit");
        } else {
          $stmt = $this->conn->prepare("$str where $option like concat('%','$search_textfield','%') $not_image $limit");
        }
        $stmt->bind_param("s", $search_textfield);
      } else {
        // echo "/ 2개";
        $stmt = $this->conn->prepare("$str where prod.prod_name like concat('%',?,'%')
            and (prod.prod_cont like concat('%',?,'%') or prod.prod_wgt like concat('%',?,'%')
            or prod.fact_name like concat('%',?,'%')) $not_image $limit");
        $stmt->bind_param("ssss", $echo_text_name, $echo_text_cont, $echo_text_cont, $echo_text_cont);
      }
    } else if ($class != "ALL" && $search_textfield != "") {
      // echo "분류 / 키워드O";
      if ($class_cd == "ALL") {
        // echo "/ 1차 전체";
        if ($echo_text_cont == "") {

          if ($option == "ALL") {
            // echo "string";
            $stmt = $this->conn->prepare("$str where
                prod.PROD_CD like concat('%',?,'%') and
                (prod.prod_name like concat('%','$search_textfield','%')  or
                prod.prod_cd like concat('%','$search_textfield','%')  or
                prod.prod_cont like concat('%','$search_textfield','%')  or
                prod.prod_wgt like concat('%','$search_textfield','%')  or
                prod.fact_name like concat('%','$search_textfield','%'))
                $not_image $limit");
          } else {
            $stmt = $this->conn->prepare("$str
                 where prod.PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%') $not_image $limit");
          }
          // echo "/ 1개";

          $stmt->bind_param("s", $class);
        } else {
          // echo "/ 2개";
          $stmt = $this->conn->prepare("$str where  prod.PROD_CD like concat('%',?,'%') and prod.prod_name like concat('%',?,'%')
              and (prod.prod_cont like concat('%',?,'%') or prod.prod_wgt like concat('%',?,'%')
              or prod.fact_name like concat('%',?,'%')) $not_image $limit");
          $stmt->bind_param("sssss", $class, $echo_text_name, $echo_text_cont, $echo_text_cont, $echo_text_cont);
        }
      } else {
        if ($class_cd_detail == "ALL") {
          // echo "/ 2차 전체";
          if ($echo_text_cont == "") {
            // echo "1개";
            if ($option == "ALL") {
              // echo "string";
              $stmt = $this->conn->prepare("$str where
                  prod.PROD_CD like concat('%',?,'%') and
                  (prod.prod_name like concat('%','$search_textfield','%')  or
                  prod.prod_cd like concat('%','$search_textfield','%')  or
                  prod.prod_cont like concat('%','$search_textfield','%')  or
                  prod.prod_wgt like concat('%','$search_textfield','%')  or
                  prod.fact_name like concat('%','$search_textfield','%')) and prod.class_cd = ?
                  $not_image $limit");
            } else {
              $stmt = $this->conn->prepare("$str
                   where prod.PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%')
                     and prod.class_cd = ? $not_image $limit");
            }
            $stmt->bind_param("ss", $class, $class_cd);
          } else {
            // echo "/ 2개";
            $stmt = $this->conn->prepare("$str where  prod.PROD_CD like concat('%',?,'%') and prod.prod_name like concat('%',?,'%')
                and (prod.prod_cont like concat('%',?,'%') or prod.prod_wgt like concat('%',?,'%')
                or prod.fact_name like concat('%',?,'%')) and prod.class_cd = ? $not_image $limit");
            $stmt->bind_param("ssssss", $class, $echo_text_name, $echo_text_cont, $echo_text_cont, $echo_text_cont, $class_cd);
          }
        } else {
          if ($echo_text_cont == "") {
            // echo "1개";
            if ($option == "ALL") {
              // echo "string";
              $stmt = $this->conn->prepare("$str where
                  prod.PROD_CD like concat('%',?,'%') and
                  (prod.prod_name like concat('%','$search_textfield','%')  or
                  prod.prod_cd like concat('%','$search_textfield','%')  or
                  prod.prod_cont like concat('%','$search_textfield','%')  or
                  prod.prod_wgt like concat('%','$search_textfield','%')  or
                  prod.fact_name like concat('%','$search_textfield','%'))
                  and prod.class_cd = ? and prod.class_detail_cd = ?
                  $not_image $limit");
            } else {
              $stmt = $this->conn->prepare("$str
                   where prod.PROD_CD like concat('%',?,'%') and $option like concat('%','$search_textfield','%')
                     and prod.class_cd = ? and prod.class_detail_cd = ? $not_image $limit");
            }

            $stmt->bind_param("sss", $class, $class_cd, $class_cd_detail);
          } else {
            // echo "/ 2개";
            $stmt = $this->conn->prepare("$str where  prod.PROD_CD like concat('%',?,'%') and prod.prod_name like concat('%',?,'%')
                and (prod.prod_cont like concat('%',?,'%') or prod.prod_wgt like concat('%',?,'%')
                or prod.fact_name like concat('%',?,'%')) and prod.class_cd = ? and prod.class_detail_cd = ? $not_image $limit");
            $stmt->bind_param("sssssss", $class, $echo_text_name, $echo_text_cont, $echo_text_cont, $echo_text_cont, $class_cd, $class_cd_detail);
          }
        }
      }
    }
    // $stmt->bind_param("sssss",$class,$class_cd,$class_cd_detail,$option,$search_textfield);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }

  //센터정보 입력 수정
  public function UpdatePack($request)
  {
    $PROD_CD = $request["PROD_CD"];//상품번호
    $PACKING_NO = $request["PACKING_NO"];//패킹번호

    if (strlen($PROD_CD) > 7) {

    }else {
      return UPDATE_FAILED;
      exit;
    }


    $str = "UPDATE TB_PROD SET PACKING_NO = '$PACKING_NO' WHERE PROD_CD = '$PROD_CD'";

    $stmt = $this->conn->prepare("$str");
    // $stmt->bind_param("sisi",$arrive,$order_no,$sel,$tm);
    $g = mysqli_error($this->conn);//에러메세지출력
    if ($stmt->execute()) {
      return UPDATE_COMPLETED;
    } else {
      return UPDATE_FAILED;
    }
  }
  // 지역 거점 전체 출력
  public function selectLogispotExcel($REQ)
  {

    $sqcCenterCd = $REQ['sqcCenterCd'];
    $sqcGroupCd = $REQ['sqcGroupCd'];
    $searchDate = $REQ['searchDate'];
    //START_DAY LAU_TIME CENTER_NAME
    //ADDR ADMIN_NAME ADMIN_TEL
    //LAU_CD LAU_ISSUE LAU_MEMO LAU_ADDR END_DAY LAD_TIME
    //BNAME ADDR LAD_NAME
    //LAD_TEL LAD_CD LAUD_S LAD_ISSUE
    //LAD_MEMO GROUP_NAME PROD_NAME
    //ADMIN_TEL2 TRUE_CD LAUD_MEMO
    //PROD_NAME1  PROD_COUNT1 PROD_NAME2 PROD_COUNT2
    $query = "SELECT 1 as KEY_NO,item.ARRIVE_DATE as START_DAY,'8:00' as LAU_TIME,cent.CENTER_NAME as CENTER_NAME,
    '서울특별시 성동구 성수동1가 708' as LAU_ADDR,'로빈' as ADMIN_NAME,'010-3491-0530' as ADMIN_TEL,
    '상차지고유코드' as LAU_CD,'상차지특이사항' as LAU_ISSUE,'상차지메모' as LAU_MEMO,item.ARRIVE_DATE as END_DAY,'10:00' as LAD_TIME,
    cust.BUSINESS_NAME as BNAME,cust.ADDR_CONT as ADDR,'하차담당자이름' as LAD_NAME,
    '하차담당자번호' as LAD_TEL,'하차지고유코드' as LAD_CD,'하차/상차구분' as LAUD_S,cust.CARRY_INFO as LAD_ISSUE,
    '하차지 메모' as LAD_MEMO,dgup.DELIV_GROUP_NAME as GROUP_NAME ,'전체물품명' as PROD_NAME,
    '010-3491-0530' as ADMIN_TEL2,'자체관리코드' as TRUE_CD,'내부메모' as LAUD_MEMO,
    '물품명1' as PROD_NAME1,'물품개수1' as PROD_COUNT1,'물품명2' as PROD_NAME2,'물품개수2' as PROD_COUNT2
                      FROM TB_ORDER_ITEM item
                      LEFT OUTER JOIN TB_ORDER od ON od.ORDER_NO = item.ORDER_NO
                      LEFT OUTER JOIN TB_CUST cust ON cust.CUST_ID = od.CUST_ID
                      LEFT OUTER JOIN TB_DELIV_SQC sqc ON cust.CUST_ID = sqc.CUST_ID
                      LEFT OUTER JOIN TB_DELIV_EXPECT_TM_CLASS etc ON cust.DELIV_CLASS_NO = etc.DELIV_CLASS_NO
  		LEFT OUTER JOIN TB_CENTER_CD cent ON cent.CENTER_CD = sqc.CENTER_CD
  		JOIN TB_DELIV_GROUP_CD dgup ON dgup.CENTER_CD = sqc.CENTER_CD and dgup.DELIV_GROUP_CD = sqc.DELIV_GROUP_CD
                      WHERE item.order_cond_cd = '02'
                         AND item.ARRIVE_DATE = '$searchDate'
                          and sqc.CENTER_CD like '$sqcCenterCd'
                          and sqc.DELIV_GROUP_CD like '$sqcGroupCd'
                       GROUP BY cust.cust_id ASC
                       order by etc.DELIV_RANKING asc,sqc.DELIV_GROUP_CD,sqc.DELIV_SQC asc";

    $stmt = $this->conn->prepare($query);

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      return $stmt;
    } else {
      return SELECT_FAILED;
    }
  }


  public function checkArriveDate($nowDate) {
      $stmt = $this->conn->prepare("SELECT DATE_FORMAT(CALENDAR_DATE, '%m-%d') AS CALENDAR_DATE,
                                    DAY,DELIV_YN FROM  TB_CALENDAR
                                    	WHERE CALENDAR_DATE = ? ");
      $stmt->bind_param("s", $nowDate);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
          return $stmt;
      } else {
          return SELECT_FAILED;
      }
  }

  // 유통사 Dday 관련 조회
  public function sellerWeekStatus($seller_id,$week){
    $stmt = $this->conn->prepare("SELECT S_NO, SELLER_ID, WEEK, WEEK_NO, DELIV_YN, TIME, S_WEEK
      FROM TB_SELLER_WEEK_INFO WHERE SELLER_ID = ? AND WEEK = ? ");
    $stmt->bind_param("ss", $seller_id,$week);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return $stmt;
    } else {
        return SELECT_FAILED;
    }
  }

  // 2021-11-05 피킹상태변경
  public function pikingUpdate($REQ)
  {
    $ORDER_ITEM_NO = $REQ['ORDER_ITEM_NO'];
    $ORDER_NO = $REQ['ORDER_NO'];
    $PICKING_YN = $REQ['PICKING_YN'];
    //로그 등록위해
    $ADMIN_ID =$REQ['ADMIN_ID'];

    $stmt = $this->conn->prepare("UPDATE
                                    TB_ORDER_ITEM
                                  SET
                                    PICKING_YN = '$PICKING_YN'
                                  WHERE
                                    ORDER_ITEM_NO = $ORDER_ITEM_NO
                                  AND
                                    order_no = $ORDER_NO");

    if ($stmt->execute()) {
      $this->RegLog($ADMIN_ID,'TB_ORDER_ITEM','PICKING_YN',$ORDER_ITEM_NO.$ORDER_NO, $PICKING_YN);
      return UPDATE_COMPLETED;
    } else {
      return UPDATE_FAILED;
    }
  }

  public function RegLog($admin_id,$table_name,$parameter,$PK, $update_data) {
  
    date_default_timezone_set('Asia/Seoul'); //타임존 설정
    $reg_date=date("Y-m-d H:i:s",time()); //현재
   
    $str = "INSERT INTO TB_DELIV_LOG(ADMIN_ID, TABLE_NAME, PARAMETER, PK, UPDATE_DATA, REG_DATE)
      VALUES('$admin_id','$table_name','$parameter','$PK', '$update_data', '$reg_date')";

    $stmt = $this->conn->prepare("$str");

    if ($result=$stmt->execute()) {
      return INSERT_COMPLETED;
    } else {
      
      return INSERT_FAILED;
    }
  }

}



// $ee = new DbOperation;
