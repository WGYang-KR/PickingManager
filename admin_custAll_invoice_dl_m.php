<?php
	session_start();
    //error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    //ini_set("display_errors", 1);

    //로그인 되어 있지 않으면 로그인 창으로 이동.
    if(!isset($_SESSION['admin_id'])) {
		echo "<meta http-equiv='refresh' content='0;url=../../admin_login.php'>";
		exit;
	}else {
		$localURL = '../../';
	}

    //쿠키 설정
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	session_start();
	header("Cache-Control: no-cache");
$_SESSION["REQUEST_URI"] = $_SERVER["REQUEST_URI"];

    //DB연결
	require_once '../php/includes/DbOperation.php';
	$db = new DbOperation();


	$search_textfield = (empty($_GET['search_textfield'])) ? '' : $_GET['search_textfield'];
	 // require('admin_menu.html');
	$condition = 'new_con';

    //헤더파일 - Adminlte, bootstrap 등
    include $localURL .'head.php';

	 if ($condition != 'new_con') {
		 require($localURL .'admin_sidebar.php');	 //사이드바SellerList()
	 }

    //검색 설정 변수에 저장.
	$searchDate = $_POST['searchDate'];
	$searchCustName = $_POST['searchCustName'];
	$searchPosition = $_POST['searchPosition'];
	$sqcCenterCd = (empty($_REQUEST['sqcCenterCd'])) ?  "ALL" : $_REQUEST['sqcCenterCd'];
	$sqcGroupCd = (empty($_REQUEST['sqcGroupCd'])) ?  "ALL" : $_REQUEST['sqcGroupCd'];
	$packingNo = (empty($_REQUEST['packingNo'])) ?  "" : $_REQUEST['packingNo']; //피킹유형: 냉동 양파, 배추 등 구분
	$dvClassCd = (empty($_REQUEST['dvClassCd'])) ?  "" : $_REQUEST['dvClassCd']; //착지시간
	$search_textfield = (empty($_REQUEST['search_textfield'])) ? '' : $_REQUEST['search_textfield'];


    //날짜 설정 안되어있으면 현재 날짜로 설정
	 if ($searchDate == '') {
	 	$searchDate = date("Y-m-d",time());
	 }

     //string을 int로
	 function replaceStrInt($str){
		 $str = str_replace(',','',$str);
		 $str = (int)$str;
		 return  $str;
	 }
?>
<style>
  table {

    border: 1px solid grey;
    border-collapse: collapse;
  }
  th{
    border: 1px solid grey;
    padding: 3px;
    background-color: #01DFD7;
  }
  td {
    border: 1px solid grey;
    padding: 3px;
  }
</style>
</head>

<body>
	<script type="text/javascript">

		// 전체검색
		function search_reset() {
			location.href = "./admin_custAll_invoice_dl_m.php";
		}

        // 날짜 변경시
		function searchDate(date){
			var form = document.searchForm;
			form.searchDate.value = date;
			form.action = "./admin_custAll_invoice_dl_m.php";
			form.submit();
		}

        //센터변경 or 센터내 그룹 변경 시
		function searchPositionFn(){
			var form = document.searchForm;
			form.action = "./admin_custAll_invoice_dl_m.php";
			form.submit();
		}

		$(document).ready(function(){ });
		function fnExcelReport(id, title) {
		    var tab_text = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
		    tab_text = tab_text + '<head><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
		    tab_text = tab_text + '<xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>'
		    tab_text = tab_text + '<x:Name>검수확인서</x:Name>';
		    tab_text = tab_text + '<x:WorksheetOptions><x:Panes></x:Panes></x:WorksheetOptions></x:ExcelWorksheet>';
		    tab_text = tab_text + '</x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';
		    tab_text = tab_text + "<table border='1px'>";
		    var exportTable = $('#' + id).clone();
		    exportTable.find('input').each(function (index, elem) { $(elem).remove(); });
		    tab_text = tab_text + exportTable.html();
		    tab_text = tab_text + '</table></body></html>';
		    var data_type = 'data:application/vnd.ms-excel';
		    var ua = window.navigator.userAgent;
		    var msie = ua.indexOf("MSIE ");

		    var fileName = title + '.xls';
		    //Explorer 환경에서 다운로드
		    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
		        if (window.navigator.msSaveBlob) {
		            var blob = new Blob([tab_text], {
		                type: "application/csv;charset=utf-8;"
		            });
		            navigator.msSaveBlob(blob, fileName);
		        }
		    } else {
		        var blob2 = new Blob([tab_text], {
		            type: "application/csv;charset=utf-8;"
		        });
		        var filename = fileName;
		        var elem = window.document.createElement('a');
		        elem.href = window.URL.createObjectURL(blob2);
		        elem.download = filename;
		        document.body.appendChild(elem);
		        elem.click();
		        document.body.removeChild(elem);
		    }
		}

		function print(param,cust_id){
			var form = document.searchForm;
			form.type.value = param;
			form.cust_id.value = cust_id;
			form.action = "./invoice/total_m.php";
			$(document.getElementById(cust_id+"_totalAllPay")).attr("name",cust_id+"_totalAllPay");
			form.submit();
		}
        function TotalReceived(){
			var form = document.searchForm;

			form.action = "./invoice/total_picking_m.php";
			form.submit();
		}
        function TotalReceived(){
			var form = document.searchForm;

			form.action = "./invoice/total_picking_m.php";
			form.submit();
		}
	</script>
	<div class="wrapper">
		<!-- Navbar -->
		<?php include $localURL .'navbar.php'?>

		<!-- Main Sidebar Container -->
		<?php

		include $localURL .'new_sidebar.php'?>
		<div class="content-wrapper">
			<section class="content-header">
				<div class="container-fluid">
					<div class="row mb-2">
						<div class="col-sm-6">
							<h1>검수확인서전체 출력(모바일)</h1>
						</div>
					</div>
				</div><!-- /.container-fluid -->
			</section>
			<!-- Main content -->
			<section class="content">

				<form name='searchForm' id='searchForm' method='post' action='./admin_custAll_invoice_dl_m.php'>
					<input type="hidden" name="type" value="">
					<input type="hidden" name="cust_id" value="">
					<div class="card">
						<div class="card-body">
							<div class="form-group">
								<div class="row">
									<!-- <div class="col-md-2 col-sm-2"> -->
										<!-- <div class="form-group"> -->
											<!-- <select class="custom-select" id="searchPosition" name="searchPosition" onchange="searchPositionFn();">
                        <?
                        // $del_position = $db->DELIVERY->selectLocalPosition();
												//
                        // if ($del_position == SELECT_FAILED) {
                        // } else {
                        //   $del_position->bind_result($no,$local_area,$local_area_class);
                        //   while ($del_position->fetch()) {
                        //     echo "<option value='$local_area_class'";
                        //     echo ($searchPosition == "$local_area_class") ? 'selected' : '';
                        //     echo "> $local_area_class </option>";
                        //   }
                        // }
                        ?>
											</select> -->
										<!-- </div> -->
									<!-- </div> -->
									<div class="col-md-7">
										<div class="form-group row">
											<div class="input-group date">
												<input type="date" class="form-control datetimepicker-input hasDatepicker"
												id="searchDate" name="searchDate" value="<?php echo $searchDate ?>">
											</div>
											<div class="form-group row col-md-3">
												<select class='custom-select' name="sqcCenterCd" onchange="searchPositionFn();">
													<option value="ALL" <? echo ($sqcCenterCd == 'ALL') ? 'selected' : ''; ?>>
														전체업장
													</option>
													<option value="none" <? echo ($sqcCenterCd == 'none') ? 'selected' : ''; ?>>
														센터미배정
													</option>
													<?php
													$selectCenterCd = $db->DELIVERY->selectCenterCd(null);

                                                if ($selectCenterCd == SELECT_FAILED) {
                                                } else {
                                                    $selectCenterCd = $db->fetchDB($selectCenterCd);
                                                    foreach ($selectCenterCd as $CenterKey => $CenterValue) {
                                                        $CENTER_CD = $CenterValue["CENTER_CD"];
                                                        $CENTER_NAME = $CenterValue["CENTER_NAME"];
                                                            echo "<option value='$CENTER_CD'";
                                                            echo ($sqcCenterCd == "$CENTER_CD") ? 'selected' : '';
                                                            echo "> $CENTER_NAME </option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group row col-md-3">
                                            <select class='custom-select' name="sqcGroupCd" onchange="searchPositionFn();" >
                                                <?php
                                                $selectDvGroupCd = $db->DELIVERY->selectDvGroupCd($_REQUEST);

                                                if ($selectDvGroupCd == SELECT_FAILED) {
                                                    echo "<option value='ALL'>배송그룹</option>";
                                                } else {?>
                                                    <option value="ALL" <? echo ($sqcGroupCd == 'ALL') ? 'selected' : ''; ?>>
                                                        전체
                                                    </option>
                                                    <option value="none" <? echo ($sqcGroupCd == 'none') ? 'selected' : ''; ?>>
                                                        그룹미배정
                                                    </option>
                                                <?
                                                    $selectDvGroupCd = $db->fetchDB($selectDvGroupCd);
                                                    foreach ($selectDvGroupCd as $DvGroupKey => $DvGroupValue) {
                                                        // DELIV_GROUP_CD, DELIV_GROUP_NAME, DELIV_GROUP_CONT, CENTER_CD, ADMIN_ID, USE_YN
                                                        $DELIV_GROUP_CD = $DvGroupValue["DELIV_GROUP_CD"];
                                                        $DELIV_GROUP_NAME = $DvGroupValue["DELIV_GROUP_NAME"];

                                                        if ($sqcGroupCd == "$DELIV_GROUP_CD") {
                                                            $excelGroup = $DELIV_GROUP_NAME;
                                                            $downYN = "display:block";
                                                        }

                                                            echo "<option value='$DELIV_GROUP_CD'";
                                                            echo ($sqcGroupCd == "$DELIV_GROUP_CD") ? 'selected' : '';
                                                            echo "> $DELIV_GROUP_NAME </option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <!-- <div class="form-group row col-md-3">
                                            <select class='custom-select' name="packingNo" onchange="searchPositionFn();">
                                                <option value="" <? echo ($packingNo == '') ? 'selected' : ''; ?>>
                                                    피킹전체
                                                </option> -->
                                            <?
                                            // $selecPack = $db->DELIVERY->selecPack(null);
																						//
                                            // if ($selecPack == SELECT_FAILED) {
                                            // } else {
                                            //     $selecPack = $db->fetchDB($selecPack);
                                            //     foreach ($selecPack as $packKey => $packValue) {
                                            //         $PACK_CLASS_NAME = $packValue["PACKING_CLASS_NAME"];
                                            //         $PACK_NO = $packValue["PACKING_NO"];
                                            //             echo "<option value='$PACK_NO'";
                                            //             echo ($packingNo == "$PACK_NO") ? 'selected' : '';
                                            //             echo "> $PACK_CLASS_NAME </option>";
                                            //     }
                                            // }


                                            ?>
                                            <!-- </select>
                                        </div> -->
                                        <div class="form-group row col-md-4">
                                            <select class='custom-select' name="dvClassCd" onchange="searchPositionFn();" >
                                                <option value="" <? echo ($dvClassCd == '') ? 'selected' : ''; ?>>
                                                모든착지시간
                                                </option>
                                                <option value="A" <? echo ($dvClassCd == 'A') ? 'selected' : ''; ?>>
                                                전체
                                                </option>
                                                <option value="V" <? echo ($dvClassCd == 'V') ? 'selected' : ''; ?>>
                                                ~시 이전
                                                </option>
                                                <option value="S" <? echo ($dvClassCd == 'S') ? 'selected' : ''; ?>>
                                                ~시 내외
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <!-- 검색창 -->
                                    <div class='col-md-15'>
                                        <div class='form-group'>
                                            <div class='input-group'>
                                                <div class='input-group-append'>
                                                    <button type='button' onclick="search_reset();" class='btn btn-default'>전체검색</button>
                                                </div>
                                                <input type='text' maxlength="256" class='form-control' id='search_textfield' name='search_textfield' placeholder='식당명 또는 ID입력' value='<?php echo $search_textfield ?>'>
                                                <div class='input-group-append'>
                                                    <button id="searchBtn" class="btn btn-default"><i class="fa fa-search"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                        <!-- ./검색 모듈 -->

                            <!--검색 결과 출력 - 검수확인서 출력 -->

								<?php
                                    /* 피킹테이블 위해 추가 - 헐크 */
                                    $P_DELIV_GROUP_CD_CHECK = "";
                                    $tempStore = 0;
                                    $noView = 0;
                                    if ($searchCondStatus == "") {
                                    $searchCondStatus = "02";
                                    }
                                    /* ./피킹테이블 위해 추가 - 헐크 */

                                    /*출력: 오더넘버, 고객id, 유통사명, 고객주소(동단위),주문날짜,배송도착지메모*/
									$custList = $db->DELIVERY->custDateAllList('02',$searchDate,$sqcCenterCd,$sqcGroupCd,null,null);

									if($custList == SELECT_FAILED) {
										// echo "<tr>없음</tr>";
									}
                                    else {
										$custList->bind_result($order_no_cust,$cust_id,$business_name_cust, $deliv_position_cust,$order_date_cust,$carry_info);

										$custProdAllList = array();

                                        $trCustCnt = 0; //기준날짜 주문 식당 수
                                        $trCustProdCnt = 0; //기준날짜 전체 상품 종류 수
                                        $businessCnt = 0;//매장수
                                        $total_price_final = 0;//총금액


                                        /*검수확인서 한 테이블 */
                                        //고객 한명씩
										while($custList->fetch())
                                        {
                                            /* 피킹테이블 위해 추가 - 헐크 */
                                            $productCnt = 0;//식당별 전체 상품 종류 수
                                            $prdPickingYesCnt =0; //피킹 완료된 상품 수 -> 나중에 productCnt와 수가 같으면 전부 피킹된것.
                                            $orderCount = 0;
                                            /* ./피킹테이블 위해 추가 - 헐크 */

											$custProdAllList["order_cond_cd"] = "02";
											$custProdAllList["cust_id"] = "$cust_id";
											$custProdAllList["searchDate"] = "$searchDate";
											$custProdAllList["center_cd"] = "$sqcCenterCd";
											$custProdAllList["group_cd"] = "$sqcGroupCd";
											$custProdAllList["packingNo"] = "$packingNo";
											$custProdAllList["dvClassCd"] = "$dvClassCd";
											$custProdAllList["search_textfield"] = "$search_textfield";


                                            //유통사별
                                            //고객 한명의 모든 주문
											$result = $db->DELIVERY->custProdAllList($custProdAllList, NULL);
                                            /* 피킹테이블 위해 추가 - 헐크 */
                                            //고객별
                                            $resultGroup = $db->DELIVERY->custProdAllList($custProdAllList,"N");
                                            /* ./피킹테이블 위해 추가 - 헐크 */

											if($result == SELECT_FAILED) {
												// echo "
												// <tr>없음</tr>
												// ";
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
												$totalMemoStr = "";

                                                //곡객 한명의 주문을 상품 단위로 추출
												$result = $db->fetchDB($result);
												foreach ($result as $resultKey => $resultValue)
                                                {
													$item_no = $resultValue["ORDER_ITEM_NO"];
													$order_no = $resultValue["ORDER_NO"];
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

                                                    //피킹 완료된 상품 수 카운트
                                                    if($PICKING_YN == 'Y') {
                                                        $prdPickingYesCnt ++;
                                                    } else {

                                                    }


                                                    /* 피킹테이블 위해 추가 */
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
                                                    /* ./피킹테이블 위해 추가 */

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

                                                    /* 피킹테이블 위해 추가*/
                                                    $currentTotalAllPay = $totalAllPayList[$tempStore];
                                                    $currentTotalMemoStr = $totalMemoStr[$tempStore];
                                                    /* 피킹테이블 위해 추가*/


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
														/*상품목록 출력부 위치*/
														$noSum++;


												}
												echo "<input type='hidden' id='$business_id"."_"."totalAllPay' value='$totalAllPay'>";

                                                    /* ./검수확인서-상품목록 */

                                                    /* 피킹 상태 버튼 */
                                                    $btnPickingStatus='';
                                                    //상품 총 가지수와 피킹된 상품 수 비교
                                                    //같으면 '완료'버튼, 다르면 '진행중' 버튼
                                                    if( $prdPickingYesCnt == $productCnt) {
                                                        $btnPickingStatus .= "
                                                        <button type='button' class='btn btn-block btn-success btn-xs'  onclick='print(\"\",\"$business_id\")'>완료</button>
                                                        ";
                                                    } else {
                                                        $btnPickingStatus .= "
                                                        <button type='button' class='btn btn-block btn-primary btn-xs'  onclick='print(\"\",\"$business_id\")'>진행중</button>
                                                        ";
                                                    }
                                                    /* ./피킹 상태 버튼 */

                                                    /* 피킹테이블 */
                                                   $resultGroup = $db->fetchDB($resultGroup);
                                                    foreach ($resultGroup as $resultGroupKey => $resultGroupValue)
                                                    {
                                                        $total_price_final =  $total_price_final+$resultGroupValue["total_price"];
                                                        $businessCnt++;
                                                        $trSumCust .= "
                                                        <tr>
                                                            <td style='text-align: center'>$businessCnt</td>
                                                            <td style='text-align: center'>".$resultGroupValue["BUSINESS_NAME"]."</td>
                                                            <td style='text-align: center'>".$productCnt."가지</td>
                                                            <td style='text-align: center'>".number_format($resultGroupValue["total_price"]) ."원</td>
                                                            <td style='text-align: center'>$btnPickingStatus</td>
                                                        </tr>
                                                        ";
                                                    }
                                                 /* ./피킹 테이블 */
										    }
                                        /* ./검수확인서 한 테이블 */

                                        $tempStore++;

                                    }

								}

                            ?>

					</div>
                    <!-- ./card-body -->
				</div>
                <!--./card-->

                <div class="card" >
                    <div class= "card-body p-0">
 
                                <table class='table-xs' style='<?php echo $trTableDisplay; ?>'>
                                    <caption><?php foreach($cgArray as $cgKey => $cgValue) { echo $cgValue; } ?> </caption>
                                    <thead>
                                    <tr>
                                        <th>배송순서</th>
                                        <th>주문매장수</th>
                                        <th>주문상품수</th>
                                        <th>주문금액</th>
                                        <th rowspan='2'>피킹여부</th>
                                    </tr>
                                    <tr>
                                        <th>합계</th>
                                        <th><?php echo $businessCnt; ?></th>
                                        <th><?php echo $trCustProdCnt;?> </th>
                                        <th><?php echo number_format($total_price_final);?>원</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        echo "$trTableCust";
                                        if ($trSumCust == "") {
                                            echo "<tr><td colspan='8' style='text-align: center'>피킹분류 상품 없음</td></tr>";
                                        }else {
                                            echo "$trSumCust";
                                        }
                                        ?>
                                    </tbody>
                                </table>

                    </div>
                    <!-- ./card-body-->
                    <div class="card-footer">
                        <!-- 총량 피킹, 작업중 버튼 -->
                        <div class="row">
                            <div class="col">
                                <button type="button" onclick="TotalReceived();" class="btn btn-info float-right">
                                    총량 피킹
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
            <!--./section-content-->
		</div>
        <!-- ./content-wrapper-->
    <div>
    <!-- ./wrapper-->

	<?php include $localURL .'footer.php'?>

	<?php include $localURL .'footer_script.php'?>

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
