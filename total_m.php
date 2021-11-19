<?php
session_start();
    //error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    //ini_set("display_errors", 1);
header("Content-Type:text/html;charset=utf-8");

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
session_start();
header("Cache-Control: no-cache");
if (!isset($_SESSION['admin_id'])) {
  echo "<meta http-equiv='refresh' content='0;url=" . $localURL . "admin_login.php'>";
  exit;
} else {
  $admin_id = $_SESSION['admin_id'];
}

  $request_url = (empty($_SESSION["REQUEST_URI"])) ? './'.$_SERVER["HTTP_REFERER"].'.php' : $_SESSION["REQUEST_URI"];
  $searchDate = $_POST['searchDate'];
  $searchCustName = $_POST['searchCustName'];

  require_once '../../php/includes/DbOperation.php';
  require_once '../../php/api/admin_function.php';
	// $db = new DbOperation();
	// $dbErp = $db->ERP;
	$db = new DbOperation();

// $totalMemoStr = $_POST['totalMemoStr'];
  $sqcCenterCd = $_POST['sqcCenterCd'];
  $sqcGroupCd = $_POST['sqcGroupCd'];
  $searchDelivtime = $_POST['searchDelivtime'];
  $searchCondStatus = $_POST['searchCondStatus'];
  $packingNo = $_POST['packingNo'];
  $dvClassCd = $_POST['dvClassCd'];
  $post_cust_id = $_POST['cust_id'];
  $totalAllPayList = number_format($_POST[$post_cust_id.'_'.'totalAllPay']) ." 원";
  $totalMemoStr = $_POST['totalMemoStr'];
  $trTableDisplay = "";
  $noneCountText = "-";
  $custServiceView = "";




?>

<!DOCTYPE html>
<html lang="kor">
  <head>
    <?
    $localURL = '../../../';
   $condition = 'new_con_1stap';
   include $localURL .'head.php';
    ?>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&amp;display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <!-- jsGrid -->
    <link rel="stylesheet" href="../../plugins/jsgrid/jsgrid.min.css">
    <link rel="stylesheet" href="../../plugins/jsgrid/jsgrid-theme.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <meta charset="utf-8">
    <title>전체 검수확인서</title>
    <!-- <style>
      body {
        margin: 0;
        padding: 0;
        font-family: Noto Sans CJK KR;
      }

      * {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
      }

      .page {
        width: 21cm;
        min-height: 29.7cm;
        padding: 0.8cm;
        margin: 0 auto;
        page-break-before: always;
      }

      table {
        border-collapse: collapse;
        page-break-inside:auto;
      }
      th, td {
        /* border: 1px solid #ced4da; */
        border-bottom: 1px solid #ced4da;
        font-size: small;
        min-height: 30px;
        height: 30px;
      }

      div.onepage {
        page-break-before: always;
      }
      @media print {
        html, body {
          width: 210mm;
          height: 297mm;
          /* margin: 2mm 2.5mm 2mm 2.5mm; */
          @page {
            size: A4;
            margin: 2mm 2.5mm 2mm 2.5mm;
            /* margin: 0; */
            /* margin-top: 20px;
            margin-left: 22.5px;
            margin-right: 22.5px; */
          }

        }
        body,  div {
          position: relative;
        }
        table, .SizeRatio {
          page-break-inside: avoid;
          -webkit-region-break-inside: avoid;
          position: relative;
        }

        .page {
          margin: 0;
          border: initial;
          width: initial;
          min-height: initial;
          box-shadow: initial;
          background: initial;
          /* page-break-after: always; */
        }
        table {
          border: 1px solid #ced4da;
          border-collapse: collapse;
        }
        tr {
          page-break-inside:avoid;
          page-break-after:auto;
          font-size: 9px;
          min-height: 30px;
          height: 30px;
        }
        th, td {
          /* border: 1px solid #ced4da; */
          border-bottom: 1px solid #ced4da;
          font-size: 9px;
          min-height: 30px;
          height: 30px;
        }
      }
      .cust_info_wrap {
        border:  1px solid #ced4da;
      }

      /* @media print {
        body,  div {
          position: relative;
        }
        table, .SizeRatio {
          page-break-inside: avoid;
          -webkit-region-break-inside: avoid;
          position: relative;
        }
      } */
    </style> -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script>

    function searchForm(date){
      date.submit();
    }

    function pikingYN(param,item_no,order_no,admin_id){
      var pyn = "";
      var checked;
      if ($(param).hasClass("jsgrid-selected-row")) {
        pyn = "N";
        checked = false;
        // console.log("비활성화시킴");
      }else {
        pyn = "Y";
        checked = true;
        // console.log("활성화시킴");
      }

      var selectedTrTop=document.getElementById(item_no+order_no+'01'); //클릭된 1행
      var selectedTrMid=document.getElementById(item_no+order_no+'02'); //클릭된 2행
      var selectedTrBtm=document.getElementById(item_no+order_no+'03'); //클릭된 3행
      // 업데이트!
      $.ajax({
        url : "./pikingUpdate.php",
        type : "post",
        data : {"ORDER_ITEM_NO":item_no,"ORDER_NO":order_no,"PICKING_YN":pyn,"ADMIN_ID":admin_id},
        error : function() {
          alert('시스템 오류입니다. 관리자에게문의.');
        },
        success : function(resultData) {
          console.log(resultData);
          //console.log(param);
         // $(param).find("input[type='checkbox']").prop('checked', checked);
         // $(param).toggleClass("jsgrid-selected-row");
      
          $(selectedTrTop).find("input[type='checkbox']").prop('checked', checked);
          $(selectedTrMid).find("input[type='checkbox']").prop('checked', checked);
          $(selectedTrTop).toggleClass("jsgrid-selected-row");
          $(selectedTrMid).toggleClass("jsgrid-selected-row");
         
          if(selectedTrBtm){
          $(selectedTrBtm).find("input[type='checkbox']").prop('checked', checked);
          $(selectedTrBtm).toggleClass("jsgrid-selected-row");
          }
        }
      });
      // $.ajax({
      //   url : "./pikingUpdate.php",
      //   type : "POST",
      //   data : {"ORDER_ITEM_NO":item_no,"ORDER_NO":order_no,"PICKING_YN":pyn}
      //   error : function(xhr, status) {
      //     alert("에러!");
      //   },
      //   success : function(resultData) {
      //     console.log(resultData);
      //     // $(param).find("input[type='checkbox']").prop('checked', checked);
      //     // $(param).toggleClass("jsgrid-selected-row");
      //   }
      // });

    }

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
  </head>
  <body>
    <?php
      //변수
      $P_DELIV_GROUP_CD_CHECK = "";
      $tempStore = 0;
      $noView = 0;

      //PHP 조건절
      if ($searchCondStatus == "") {
        $searchCondStatus = "02";
      }

      //MAIN SELECT
      $custList = $db->DELIVERY->custDateAllList($searchCondStatus,$searchDate,$sqcCenterCd,$sqcGroupCd,$searchDelivtime,$post_cust_id);
      if($custList == SELECT_FAILED) {
        // echo "<tr>없음</tr>";
      } else {
        $custList->bind_result($order_no_cust,$cust_id,$business_name_cust, $deliv_position_cust,$order_date_cust,$carry_info);
        
        $custProdAllList = array();
        while($custList->fetch()) {
          //custProdAllList() 파라미터
          $custProdAllList["order_cond_cd"] = "$searchCondStatus";
          $custProdAllList["cust_id"] = "$cust_id";
          $custProdAllList["searchDate"] = "$searchDate";
          $custProdAllList["center_cd"] = "$sqcCenterCd";
          $custProdAllList["group_cd"] = "$sqcGroupCd";
          $custProdAllList["packingNo"] = "$packingNo";
          $custProdAllList["dvClassCd"] = "$dvClassCd";
          //관리자 메모 불러오기
          
          $admin_memo = $db->Order->selectOrderAdminMemo($cust_id);

         
          $result = $db->DELIVERY->custProdAllList($custProdAllList,NULL);

          $resultGroup = $db->DELIVERY->custProdAllList($custProdAllList,"N");
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
              $ITEM_MEMO= $resultValue["ITEM_MEMO"];
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
              // $currentTotalAllPay = $totalAllPayList[$tempStore];
              $currentTotalAllPay = $totalAllPayList;
              $currentTotalMemoStr = $totalMemoStr[$tempStore];
              if ($noSum == 0) {
              ?>
              <div class='onepage' >
                <div class='page' data-id='<?php echo $order_no_cust ?>'>
                  <input type='hidden' name='excelName' id='<?php echo $order_no_cust ?>' value='<?php echo $excelName ?>'/>

                    <section class="content">
                      <div class="card" style="margin:10px;">
                        <div class="card-header">
                          <h3 class="card-title">
                            <?php echo $business_name ?>
                            <?php echo "(".$P_CENTER_NAME.")"; ?>
                            <?php echo $P_DELIV_GROUP_CD.$noView.$sqcText ?></h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                              <i class="fas fa-minus"></i>
                            </button>
                            <!-- <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                              <i class="fas fa-times"></i>
                            </button> -->
                          </div>
                        </div>
                        <div class="card-body p-0" style="display: block;">
                          <table class="table table-striped projects">
                              <thead>
                              </thead>
                              <tbody>
                            
                                  <tr>
                                      <td>
                                          주문일자
                                      </td>
                                      <td>
                                          <a>
                                              <?php echo $order_date_cust ?>
                                          </a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          매장명
                                      </td>
                                      <td>
                                          <a>
                                            <?php echo $business_name ?>
                                            <?php echo "(".$P_CENTER_NAME.")"; ?>
                                            <?php echo $P_DELIV_GROUP_CD.$noView.$sqcText ?></h3>
                                          </a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          배송시간
                                      </td>
                                      <td>
                                          <a>
                                            <?php
                                            if ($DELIV_RANKING >= 0 && strlen($DELIV_CONT) > 0) {
                                              echo $DELIV_RANKING."차 / ". $DELIV_CONT;
                                            }else {
                                              echo "-";
                                            }
                                            ?>
                                          </a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          주문금액
                                      </td>
                                      <td>
                                          <a>
                                              <?php echo $currentTotalAllPay ?>
                                          </a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          주소
                                      </td>
                                      <td>
                                          <a>
                                              <?php echo $seller_addr ?>
                                          </a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          요청사항
                                      </td>
                                      <td>
                                          <a>
                                              <? echo $currentTotalMemoStr ?>
                                          </a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          관리자 메모
                                      </td>
                                      <?php 
                                      if ($admin_memo == SELECT_FAILED) {
                                        echo "
                                                <td>없음</td>
                                        ";
                                      } else {
                                        $admin_memo->bind_result($am_admin_no, $am_admin_id, $am_cust_id, $am_url, $am_memo, $am_reg_date);

                                        while ($admin_memo->fetch()) {
                                          if ($am_memo == "") {
                                            echo "<td>없음</td>";
                                          } else {
                                            echo "<td><strong>$am_memo</strong></td>";
                                          }
                                        }
                                      }
                                      ?>
                                  </tr>
                                  <tr>
                                      <td>
                                          인도처정보
                                      </td>
                                      <td>
                                          <a>
                                              <?php echo $carry_info ?>
                                          </a>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>
                        </div>
                        <!-- /.card-body -->
                      </div>
                      <div class="card" style="margin:10px; ">
                        <div class="card-body p-0">
                            <div id="jsGrid1" class="jsgrid" style="position: relative; height: 100%; width: 100%;">
                                <div class='jsgrid-grid-body' >
                                    <span>★상품클릭시 피킹확인상태로 활성화됩니다.</span>
                                    <table class='jsgrid-table'  id='exportTable_<?php echo $order_no_cust ?>'>
                                        <tbody>
                                          <tr class="jsgrid-header-row">
                                              <th rowspan='2' class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style="width: 5%; padding: 0;">순번</th>
                                              <th class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style="width: 20%; padding: 0;">유통사</th>
                                              
                                              <th rowspan='2'class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style="width: 5%; padding: 0;">수량</th>
                                              <th class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style="width: 20%; padding: 0;">중량</th>
                                              
                                              <th class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style="width:15%; padding: 0;">분류</th>
                                              
                                          </tr>
                                          <tr class="jsgrid-header-row">
                                            <th class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style="padding: 0;">상품명</th>
                                            <th class="jsgrid-header-cell jsgrid-align-center" style="padding: 0;">상품상세</th>
                                            <th class="jsgrid-header-cell jsgrid-align-center jsgrid-header-sortable" style=" padding: 0;">Y/N</th>
                                          </tr>
                  <?
              }
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
                $stnCondStr = "<strong>*</strong></br>";
              }
              $noSum++;
                if ($prod_cont == "") {
                  $prod_cont = " -- ";
                  if ($fact_name != "") {
                    $fact_name = "/".$fact_name;
                  }
                }else {
                  if ($fact_name != "") {
                    $fact_name = "/".$fact_name;
                  }
                }

                if ($PICKING_YN == "Y") {
                  $jsgridSelect = "jsgrid-selected-row";
                  $jsgridChecked = "checked ='true'";
                }else {
                  $jsgridSelect = "";
                  $jsgridChecked = "";
                }
                // 활성화클래스
                 //
         
                 $rowspan=2;
                 if($ITEM_MEMO !="") {
                   $rowspan=3;
                 }
                $trstr = " 

                    <tr class='jsgrid-row $jsgridSelect' id='$item_no$order_no"."01' onClick='pikingYN(this,\"$item_no\",\"$order_no\",\"$admin_id\")'>
                        <td rowspan='$rowspan' class='jsgrid-cell jsgrid-align-center' style=' padding: 2px;   border-top: 1px solid #444444;'>$stnCondStr $noSum</td>
                        <td class='jsgrid-cell jsgrid-align-center' style=' padding: 2px;border-top: 1px solid #444444;'>$seller_name</td>
                        
                        <td rowspan='2' class='jsgrid-cell jsgrid-align-center' style='padding: 2px;border-top: 1px solid #444444;'>$prod_order_cnt</td>
                        <td class='jsgrid-cell jsgrid-align-center' style=' padding: 2px;border-top: 1px solid #444444;'>$prod_wgt</td>
                        
                        <td class='jsgrid-cell jsgrid-align-center' style='padding: 2px;border-top: 1px solid #444444;'>$P_PACKING_CLASS_NAME</td>
                        
                    </tr>
                    <tr class='jsgrid-row $jsgridSelect' id='$item_no$order_no"."02' onClick='pikingYN(this,\"$item_no\",\"$order_no\",\"$admin_id\")'>
                      <td class='jsgrid-cell jsgrid-align-center' style=' padding: 2px;'>$prod_name</td>
                      <td class='jsgrid-cell jsgrid-align-center' style='padding: 2px;'>$prod_cont"."$fact_name</td>
                      <td class='jsgrid-cell jsgrid-align-center' style=' padding: 2px;'><input type='checkbox' $jsgridChecked disabled=''></td>
                    </tr>
                ";
                
                //상품별 메모 출력
                if($ITEM_MEMO != "") {
                $trstr .="

                  <tr class='jsgrid-row $jsgridSelect' id='$item_no$order_no"."03' onClick='pikingYN(this,\"$item_no\",\"$order_no\",\"$admin_id\")'>
                    <td colspan='4' class='jsgrid-cell jsgrid-align-center' style=' padding: 2px; '>$ITEM_MEMO</td>
                  </tr>
                      
                ";
                }
                //
                // $trstr = "
                //   <tr>
                //     <td style='width:30px;text-align: center;$cssStr'>$stnCondStr $noSum</td>
                //     <td style='width:130px;text-align: center;$cssStr'>$seller_name</td>
                //     <td style='width:130px;text-align: center;$cssStr'>$prod_name</td>
                //     <td style='width:30px;text-align: center;$cssStr'>$prod_order_cnt</td>
                //     <td style='width:40px;text-align: center;$cssStr'>$prod_wgt</td>
                //     <td style='width:250px;text-align: center;$cssStr'>$prod_cont"."$fact_name</td>
                //     <td style='width:130px;text-align: center;$cssStr'>$P_PACKING_CLASS_NAME</td>
                //   </tr>
                // ";

              echo $trstr;
            }
                  echo "
                  </tbody>
              </table>
              <div class='invoice p-3 mb-3'>
                <div class='row no-print'>
                  <div class='col-12'>
                    <form method='post' action='../admin_custAll_invoice_dl_m.php' onClick='searchForm(this);'>";
                      foreach ($_REQUEST as $reKey => $reValue) {
                        if (isset($reValue) && !empty($reValue) && $reValue != "") {
                          echo "<input type='hidden' name='$reKey' value='$reValue'/>";
                        }
                      }
                      echo"

                      <button rel='noopener' class='btn btn-default'>이전화면</button>
                      <button type='button' class='btn btn-success float-right'>
                        피킹완료
                      </button>
                    </form>
                  </div>
                </div>
              </div>
          </div>
                  <input type='hidden' class='all_total'  id='{$business_id}_total' value='$totalAllPay'/>
            </div>
        </div>
      </div>
    </section>
            $custServiceView
            </div>
          <br style='height:0; line-height:0'>
        </div>
          ";
        }
        $tempStore++;
      }
    }
   ?>

   <!-- jQuery -->
   <script src="../../plugins/jquery/jquery.min.js"></script>
   <!-- Bootstrap 4 -->
   <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
   <!-- jsGrid -->
   <script src="../../plugins/jsgrid/demos/db.js"></script>
   <script src="../../plugins/jsgrid/jsgrid.min.js"></script>
   <!-- AdminLTE App -->
   <script src="../../dist/js/adminlte.min.js"></script>
   <!-- AdminLTE for demo purposes -->
   <script src="../../dist/js/demo.js"></script>
  </body>
</html>
