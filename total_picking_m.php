<?php
	session_start();
    //error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    //ini_set("display_errors", 1);

      //로그인 되어 있지 않으면 로그인 창으로 이동.
  if(!isset($_SESSION['admin_id'])) {
    echo "<meta http-equiv='refresh' content='0;url=admin_login.php'>";
    exit;
  }else {
    $localURL = '../../../';
  }


  //error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
  //ini_set("display_errors", 1);

  //header("Content-Type:text/html;charset=utf-8");
  $searchDate = $_POST['searchDate'];
  $searchCustName = $_POST['searchCustName'];

  require_once '../../php/includes/DbOperation.php';
  require_once '../../php/api/admin_function.php';
	// $db = new DbOperation();
	// $dbErp = $db->ERP;
	$db = new DbOperation();

  $condition = 'invoice';
  include $localURL .'head.php';

  $totalAllPayList = $_POST['totalAllPay'];
  $totalMemoStr = $_POST['totalMemoStr'];
  // $totalMemoStr = $_POST['totalMemoStr'];
  $sqcCenterCd = $_POST['sqcCenterCd'];
  $sqcGroupCd = $_POST['sqcGroupCd'];
  $searchDelivtime = $_POST['searchDelivtime'];
  $searchCondStatus = $_POST['searchCondStatus'];
  $packingNo = $_POST['packingNo'];
  $dvClassCd = $_POST['dvClassCd'];

?>

    <script>
    function priceToString(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
      $(document).ready(function(){
        $("[class^=all_total]").each(function() {
          var $thisTotal = $(this).val();
          var $thisId = $(this).attr("id");
          var thisArr = $thisId.split('_');
          var id = thisArr[0];
          var formatTotal = priceToString($thisTotal);
          $('#'+id).text("₩ "+formatTotal);
        });
      });
    </script>
 <style>
  table {

    border: 1px solid #444444;
    border-collapse: collapse;
  }
  th{
    border: 1px solid #444444;
    padding: 2px;
    background-color: #01DFD7;
  }
  td {
    border: 1px solid #444444;
    padding: 2px;
  }
</style>
  </head>
  <body class="">

    <div class="wrapper">

      <div class="content-wrapper">
        <section class="content-header">
          <div class="container-fluid">
            <div class="row mb-2">
              <div class="col-sm-6">
                <h1>총량 피킹(모바일)</h1>
              </div>
            </div>
          </div><!-- /.container-fluid -->
        </section>
        <section class="content">
          <div class="card">
            <div class="card-body p-0">

<?php
    /* 연산부 */
    $P_DELIV_GROUP_CD_CHECK = "";
      $tempStore = 0;
      $noView = 0;
      if ($searchCondStatus == "") {
        $searchCondStatus = "02";
      }
      $custList = $db->DELIVERY->custDateAllList($searchCondStatus,$searchDate,$sqcCenterCd,$sqcGroupCd,$searchDelivtime,null);
      if($custList == SELECT_FAILED) {
        // echo "<tr>없음</tr>";
      } else {
        $custList->bind_result($order_no_cust,$cust_id,$business_name_cust, $deliv_position_cust,$order_date_cust,$carry_info);
        $trSum ="";
        $trSumArray =array();
        $custProdAllList = array();
        $trCustCnt = 0;
        $trCustProdCnt = 0;
        $businessCnt = 0;//매장수
        $total_price_final = 0;//총금액
        while($custList->fetch()) {
          $productCnt = 0;//식당 상품수

          $orderCount = 0;
          $custProdAllList["order_cond_cd"] = "$searchCondStatus";
          $custProdAllList["cust_id"] = "$cust_id";
          $custProdAllList["searchDate"] = "$searchDate";
          $custProdAllList["center_cd"] = "$sqcCenterCd";
          $custProdAllList["group_cd"] = "$sqcGroupCd";
          $custProdAllList["packingNo"] = "$packingNo";
          $custProdAllList["dvClassCd"] = "$dvClassCd";

          $result = $db->DELIVERY->custProdAllList($custProdAllList,NULL);

          $resultGroup = $db->DELIVERY->custProdAllList($custProdAllList,"N");
          // $result = $db->DELIVERY->custProdAllList($searchCondStatus,$cust_id,$searchDate,$sqcCenterCd,$sqcGroupCd,$packingNo);
          if($result == SELECT_FAILED) {
            echo "
            <tr>없음.</tr>
            ";
          } else {

            if ($P_DELIV_GROUP_CD_CHECK == $P_DELIV_GROUP_CD) {
              $noView++;
            }


            $totalAllPrice = 0;    // 단가총액 합계
            $totalAllCost = 0;		 // 공급가액 합계
            $totalAllTax = 0;    // 세액 합계
            $totalAllCoupon = 0;    // 할인 합계
            $totalAllPay = 0;    // 총주문금액 합계
            $noSum = 0;

            $result = $db->fetchDB($result);
            foreach ($result as $resultKey => $resultValue) {
              $item_no = $resultValue["ORDER_ITEM_NO"];
              $order_no = $resultValue["ORDER_NO"];
              // echo "$order_no";
              $sellCd = $resultValue["SELLER_PROD_CD"];
              $seller_id = $resultValue["SELLER_ID"];
              $seller_name = $resultValue["SELLER_NAME"];
              $seller_telNo = $resultValue["TEL_NO"];
              $seller_addr = $resultValue["ADDR_CONT"];
              $order_cond_cd = $resultValue["order_cond_cd"];
              $prod_cd = $resultValue["PROD_CD"];
              $prod_name = $resultValue["PROD_NAME"];
              $prod_cont = $resultValue["PROD_CONT"];
              $prod_wgt = $resultValue["PROD_WGT"];
              $fact_name = $resultValue["FACT_NAME"];
              $prod_order_cnt = $resultValue["PROD_ORDER_CNT"];
              $order_costpr = $resultValue["order_costpr"];
              $taxfree_yn = $resultValue["TAXFREE_YN"];
              $tax_pay = $resultValue["tax_pay"];
              $total_price = $resultValue["total_price"];
              $coupon_price = $resultValue["coupon_price"];
              $pay_price = $resultValue["pay_price"];
              $deadline_tm = $resultValue["dead_tm"];
              $business_name = $resultValue["BUSINESS_NAME"];
              $business_id = $resultValue["CUST_ID"];
              $deliv_position = $resultValue["DELIV_POSITION"];
              $order_date = $resultValue["ARRIVE_DATE"];
              $stn_cond_cd = $resultValue["STN_COND_CD"];
              $DELIV_RANKING = $resultValue["DELIV_RANKING"];
              $DELIV_CONT = $resultValue["DELIV_CONT"];
              $P_CENTER_CD = $resultValue["CENTER_CD"];
              $P_CENTER_NAME = $resultValue["CENTER_NAME"];
              $P_DELIV_GROUP_CD = $resultValue["DELIV_GROUP_CD"];
              $P_DELIV_SQC = $resultValue["DELIV_SQC"];
              $P_PACKING_CLASS_NAME = $resultValue["PACKING_CLASS_NAME"];
              $PICKING_YN=$resultValue["PICKING_YN"];

             // $trCustCnt++;
              $trCustProdCnt++;//상품가짓수...
              $productCnt++;//식당 상품수

              if ($P_DELIV_GROUP_CD_CHECK !== $P_DELIV_GROUP_CD) {
                $noView = 1;
              }
              $P_DELIV_GROUP_CD_CHECK = $P_DELIV_GROUP_CD;
              if ($P_DELIV_SQC == 0) {
                $sqcText = "<span style='color:red;font-weight: bolder;'> $noneCountText</span>";
              }else {
                $sqcText = "";
              }
              if ($seller_id == 'deliverylab') {
                $order_costpr = $total_price;
              }
              $totalAllPrice = $totalAllPrice+$order_costpr;
              $totalAllCost = $totalAllCost+$total_price;
              $totalAllTax = $totalAllTax+$tax_pay;
              $totalAllCoupon = $totalAllCoupon+$coupon_price;
              $totalAllPay = $totalAllPay+$pay_price;
              $tseller_telNo =  '(tel.) '.$seller_telNo;
              $excelName = $searchDate.'_'.$business_name;

              // $totalAllPrice = number_format($totalAllPrice);
              // $totalAllTax = number_format($totalAllTax);
              // $totalAllCost = number_format($totalAllCost);
              // $totalAllCoupon = number_format($totalAllCoupon);
              // $totalAllPay = number_format($totalAllPay);
              $currentTotalAllPay = $totalAllPayList[$tempStore];
              $currentTotalMemoStr = $totalMemoStr[$tempStore];

              $order_costpr = number_format($order_costpr);
              $tax_pay = number_format($tax_pay);
              $total_price = number_format($total_price);
              $coupon_price = number_format($coupon_price);
              $pay_price = number_format($pay_price);
              $cssStr = '';
              if ($deadline_tm == "택배상품") {
                $cssStr = 'background-color: yellow;';
                if ($prod_cd == "E0000000" || $prod_cd == "E0000002") {
                  $deadline_tm = 'D-1';
                  $cssStr = '';
                }
              }
              if ($coupon_price == 0) {
                $coupon_price = '';
              }
              $stnCondStr = "";
              if ($stn_cond_cd == "01") {
                $stnCondStr = "* ";
              }
              $noSum++;

              //총합체크
              $prdPickingYesCnt =0; //피킹 완료된 상품 수 -> 나중에 productCnt와 수가 같으면 전부 피킹된것.
              $totalPickingCnt=0;
              $didFinishPicking = '';
              if (strlen($P_PACKING_CLASS_NAME) > 0 && $P_PACKING_CLASS_NAME != "") {
                //매장별-총합피킹여부 표시 위한 카운트
                if($PICKING_YN == 'Y') {
                  $prdPickingYesCnt ++;
                }
                $totalPickingCnt++;

                if (
                  in_array($prod_cd,$trSumArray["$prod_cd"."_"."$seller_id"])
                  && in_array($seller_id,$trSumArray["$prod_cd"."_"."$seller_id"])
                ) {//포함되어있다면... 결과값을 누적함

                  //총합 피킹 표시
                  if( $prdPickingYesCnt == $totalPickingCnt) {
                    $didFinishPicking = '완료' ;
                    } else {
                      $didFinishPicking = '<span style="background-color:yellow">미완료</span>' ;
                    }
                  $trSumArray["$prod_cd"."_"."$seller_id"]["PROD_ORDER_CNT"] = $trSumArray["$prod_cd"."_"."$seller_id"]["PROD_ORDER_CNT"]+$prod_order_cnt;
                  $trSumArray["$prod_cd"."_"."$seller_id"]["BUSINESS"] = $trSumArray["$prod_cd"."_"."$seller_id"]["BUSINESS"]." , $business_name".chr(91)."$prod_order_cnt".chr(93).chr(91)."$didFinishPicking".chr(93);



                }else {//아무것도 없다면..결과값 그대로 넣음..
                  if( $prdPickingYesCnt == $totalPickingCnt) {
                    $didFinishPicking = '완료' ;
                    } else {
                      $didFinishPicking = '<span style="background-color:yellow">미완료</span>' ;
                    }

                  $trSumArray["$prod_cd"."_"."$seller_id"] = $resultValue;
                  $trSumArray["$prod_cd"."_"."$seller_id"]["BUSINESS"] = "$business_name".chr(91)."$prod_order_cnt".chr(93).chr(91)."$didFinishPicking".chr(93);

                }

              }


            }

            $resultGroup = $db->fetchDB($resultGroup);
            foreach ($resultGroup as $resultGroupKey => $resultGroupValue) {
              $total_price_final =  $total_price_final+$resultGroupValue["total_price"];
              $businessCnt++;

            }

        }
        $tempStore++;
      }
    }

    //유통사정렬~~
  function returnSumArray($array){//배열정렬
    $array = array_unique($array);//중복제거
    sort($array);//정렬
    return $array;
  }
/* ./연산부 */
?>
<?php
/* 출력부 */


/*유통사, 피킹분류, 상품명 순으로 정렬 */
// 열 목록 얻기 http://docs.php.net/manual/kr/function.array-multisort.php -> 예제3 참고
foreach ($trSumArray as $key => $row) {
  $arrProdName[$key]  = $row['PROD_NAME'];
  $arrPackingClass[$key] = $row['PACKING_CLASS_NAME'];
  $arrSellerName[$key] = $row['SELLER_NAME'];
}
// volume 내림차순, edition 오름차순으로 데이터를 정렬
// 공통 키를 정렬하기 위하여 $data를 마지막 인수로 추가
array_multisort($arrSellerName,SORT_ASC, $arrPackingClass, SORT_ASC, $arrProdName, SORT_ASC,   $trSumArray);
/* ./정렬 */

$trTable = "";
$trTableCust = "";
$trSumCnt = 0;
  foreach ($trSumArray as $trSumkey => $trSumValue) {
    array_push($cgArray,$trSumValue["CENTER_NAME"].$trSumValue["DELIV_GROUP_CD"]);
    // var_dump($trSumValue["BUSINESS"]);
    $trSumCnt++;
    $trSum .= "
    <tr>
      <td rowspan='2' style='text-align: center'>$trSumCnt</td>
      <td style='text-align: center'>".$trSumValue["PROD_CD"]."</td>
      <td rowspan='2' style='text-align: center'>".$trSumValue["PROD_NAME"]."</td>
      <td rowspan='2' style='text-align: center'>".$trSumValue["PROD_ORDER_CNT"]."</td>
      <td style='text-align: center'>".$trSumValue["PROD_WGT"]."</td>
      <td rowspan='2' style='text-align: center'>".$trSumValue["PACKING_CLASS_NAME"]."</td>

    </tr>
    <tr>
      <td style='text-align: center'>".$trSumValue["SELLER_NAME"]."</td>
      <td style='text-align: center'>".$trSumValue["PROD_CONT"]."</td>
    </tr>
    ";
    $trSum .= "
    <tr>
      <td style='text-align: center; border-bottom-width:2px ' colspan='6'>".$trSumValue["BUSINESS"]."</td>
    </tr>
    ";
  }
$cgArray = returnSumArray($cgArray);


              $trTable .= "<table class='table-xs'>
                             <caption>";
                              //제목
                              foreach ($cgArray as $cgKey => $cgValue) {
                                $trTable .= "$cgValue ";
                              }
              $trTable .= "  </caption>

                              <thead>
                              <tr>
                                <th rowspan='2'style='width:5%'>순번</th>
                                <th style='width:20%'>MS코드</th>
                               
                                <th rowspan='2'style='width:20%'>상품명</th>
                                <th rowspan='2'style='width:5%'>수량</th>
                                <th style='width:20%'>중량</th>
                                
                                <th rowspan='2'style='width:5%'>피킹분류</th>
                              </tr>
                              <tr>
                              <th >유통사</th>
                              <th >상품상세</th>
                              </tr>
                              </thead>

                              <tbody>";
          echo "$trTable";
          if ($trSum == "") {
            echo "<tr><td colspan='6' style='text-align: center; border-bottom:2px'>피킹분류 상품 없음</td></tr>";
          }else {
            echo "$trSum";
          }

          echo "</tbody>
          </table>";
/* ./출력부 */
?>

            </div>
            <!--./card-body-->
            <div class="card-footer">
                        <!-- 총량 피킹, 작업중 버튼 -->
                        <div class="row">
                            <div class="col">
                                <button type="button" onclick="history.back();" class="btn btn-info">
                                    이전
                                </button>
                            </div>
                            <!-- ./col-->
                        </div>
                        <!-- ./row -->
                    </div>
                    <!-- ./card-footer-->
          </div>
          <!-- ./card -->
        </section>
        <!-- section-content-->
      </div>
      <!--content-wrapper-->

    </div>
  <!--wrapper-->


<script>
  $("#searchDate").datepicker({
    locale: {
      format: 'YYYY-MM-DD'
    },
  });

  /* jQuery Part */
  $("#searchDate").change(function() {
    var date = $('#searchDate').val();
    searchDate(date);
  });
  $("#searchDate").click(function() {
    setTimeout(function(){
      $("#ui-datepicker-div").css("display","none");
    },50);
  });
</script>

</body>
</html>
