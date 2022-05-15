<?php
error_reporting(E_ALL);
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'])) {
	header('Location: index.php?response=notloggedin');
	die;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		
		<title>Smart Simple Bot - Bybit Edition <img src="https://s1.bycsi.com/asset/image/logo-white.svg"></img></title>
		
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4-4.0.0/jq-3.2.1/dt-1.10.16/r-2.2.1/datatables.min.css"/> 
		<script type="text/javascript" src="https://cdn.datatables.net/v/bs4-4.0.0/jq-3.2.1/dt-1.10.16/r-2.2.1/datatables.min.js"></script>

		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
			integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
		</script>

		<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
        
		<!--  -->
		<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>	

		<link href="css/style.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="js/ajax.js"></script>
		<script>

		function get_status() {
			$.ajax({
				url: "requesthandler.php?action=load_status",
				context: document.body,
				success: function (response) {
					if (response == 'ERROR_NOT_LOGGED_IN') {
						location.href = 'logout.php?response=incorrect_ajax_call';
					} else {
						$('#status').append(response);
						setTimeout(function () {
							sendRequest(); //this will send request again and again;
						}, 5000);
					}
				}
			});
		}


		$(document).ready(function () {
			var $loading = $('.loading').hide();
			$(document)
				.ajaxStart(function () {
					$loading.show();
				})
				.ajaxComplete(function () {
					$(".input_float").inputFilter(function (value) {
							return /^-?\d*[.]?\d{0,2}$/.test(value);
						}),
						$(".input_number").inputFilter(function (value) {
							return /^-?\d*$/.test(value);
						});
				})
				.ajaxStop(function () {
					$loading.hide();
				});

			$(function () {
				$("#dialog").dialog({
					autoOpen: false
				});
			});

			

			$.ajax({
				url: "requesthandler.php?action=load_all_accounts",
				context: document.body,
				success: function (response) {
					if (response == 'ERROR_NOT_LOGGED_IN') {
						location.href = 'logout.php?response=incorrect_ajax_call';
					} else {
						$('#accounts').prepend(response);
						$('#account_table').DataTable({
							responsive: true,
							columnDefs: [{
								responsivePriority: 1,
								targets: 0
								},
								{
								responsivePriority: 10001,
								targets: 4
								},
								{
								responsivePriority: 2,
								targets: -2
								}
							],
							"searching": false,
							"paging": false,
							"ordering": false,
							"info": false,
							order: [ 
								( [ 8, 'desc' ] ) 
							] 
						});
					}
				}
			});

			$.ajax({
				url: "requesthandler.php?action=load_status",
				context: document.body,
				success: function (response) {
					if (response == 'ERROR_NOT_LOGGED_IN') {
						location.href = 'logout.php?response=incorrect_ajax_call';
					} else {
						$('#status').append(response);
					}
				}
			});

			
		});
		</script>
	</head>
	
	<body class="loggedin">

		<div class="loading">
			<div class="spinner">
			Loading...
			</div>
		</div>

		<nav class="navtop">
			<div>
				<h1>Smart Simple Bot - Bybit Edition</h1>
				<a href="#" id="status"> <i class="fas fa-wifi"></i> </a>
                <a class="debug_log_link"><i class="fas fa-bug"></i>Debug log</a>
				<a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</nav>

		<div class="content">
        
            <div class="home hide"><a class="back_home_link"><i class="fas fa-home"></i> Back to home</a></div>
       
            <div class="workspace">
                <h2>Bots</h2>

                <div id="accounts">
                    <i class="fas fa-plus"></i>  <a class="add_account_link" ac_id="1"> Add bot</a>
                    </div>

                    <div id="dialog" title="Notice">
                </div>
            </div>
                  

			<div class="workspace hide">

				<!-- Div for adding an account -->
				<div class="add_account hide">

					<h2> Add an account </h2>
               
					<form method="POST" id="add_account" onsubmit="return a_add_account();">
						<input type="hidden" name="action" id="action" value="add_account" />
                        <div class="field">
							<label> Account ID: (Can be any value but make sure you keep them unique for each account)  </label>
							<input type="text" maxlength="10" name="bot_account_id" id="name" /> 
						</div>
						<div class="field">
							<label> Name:  </label>
							<input type="text" name="account_name" id="name" /> 
						</div>
						<div class="field">
							<label> API Key:  </label>
							<input type="text" name="api_key" id="api_key" />
						</div> 
						<div class="field">
							<label> API Secret: </label>
							<input type="text" name="api_secret" id="api_secret" /> 
						</div>
						<input type="submit" name="submit_form" value="Submit">
					</form>
				</div>

				<!-- Div for editing an bot -->
				<div class="advanced_settings hide">

					<ul class="nav nav-tabs" id="myTab" role="tablist">
						<li class="nav-item" role="presentation">
						<button class="nav-link active" id="bot_settings-tab" data-bs-toggle="tab"
							data-bs-target="#bot_settings" type="button" role="tab" aria-controls="bot_settings"
							aria-selected="true">Bot settings</button>
						</li>
						<li class="nav-item" role="presentation">
						<button class="nav-link" id="tv_alerts-tab" data-bs-toggle="tab" data-bs-target="#tv_alerts"
							type="button" role="tab" aria-controls="tv_alerts" aria-selected="false">TradingView alerts</button>
						</li>
					</ul>
					<div class="tab-content" id="myTabContent">
						<div class="tab-pane fade show active" id="bot_settings" role="tabpanel"
						aria-labelledby="bot_settings-tab">
						</div>
						<div class="tab-pane fade" id="tv_alerts" role="tabpanel" aria-labelledby="tv_alerts-tab">
						</div>
					</div>

				</div>

				<!-- Edit account -->
				<div class="manage_bots hide">
                </div>

                <!-- TV Alerts -->
				<div class="tv_alerts hide">
                </div>

                <!-- Telegram settings -->
				<div class="telegram_settings hide">
                </div>

                <!-- Logbook -->
				<div class="logbook hide">
                </div>

                <!-- Debug log -->
				<div class="debug_log hide">
                </div>

			</div>
		</div>
	</body>
</html>