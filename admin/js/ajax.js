



// Action to add an account
function a_add_account()
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=add_account',
        data: $("#add_account").serialize(),
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

// Action to disable an account
function a_disable_account(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=disable_account&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

// Action to enable an account
function a_enable_account(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=enable_account&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

// Action to delete an account
function a_delete_account(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=delete_account&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

// Set active deals
function a_update_max_active_deals(id , deals)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_max_active_deals&id=' + id + '&deals=' + deals,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

// Set active deals
function a_update_mad_direction(id , direction)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_mad_direction&id=' + id + '&direction=' + direction,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

// Set bo size
function a_update_bo_size(id , size)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_bo_size&id=' + id + '&size=' + size,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set leverage
function a_update_leverage(id , leverage)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_leverage&id=' + id + '&leverage=' + leverage,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set leverage
function a_update_leverage_mode(id , leverage_mode)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_leverage_mode&id=' + id + '&mode=' + leverage_mode,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set leverage
function a_update_hedge(id , hedge_mode)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_hedge&id=' + id + '&mode=' + hedge_mode,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}



// Set stop loss setting
function a_update_stoploss(id , use_stoploss)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_stoploss&id=' + id + '&setting=' + use_stoploss,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set leverage
function a_update_stoploss_percentage(id , percentage)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_stoploss_percentage&id=' + id + '&percentage=' + percentage,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set away closure
function a_update_away_mode(id , status)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_away_mode&id=' + id + '&status=' + status,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set away closure
function a_update_away_closure(id , percentage)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_away_closure&id=' + id + '&percentage=' + percentage,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set away_stoploss
function a_update_away_stoploss(id , percentage)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_away_stoploss&id=' + id + '&percentage=' + percentage,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}

// Set bot Status
function a_update_active(id , status)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=update_active&id=' + id + '&status=' + status,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false; 
}


// Load all bots
function a_load_bots(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=load_bots&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $('.manage_bots').append(response);
            }
        }
    });


    
    return false; 
}

// Load TV alerts settings
function a_load_tv_alerts(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=load_tv_alerts&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $('#tv_alerts').append(response);
                $('#bot_spec_alerts').DataTable({

                    "pageLength": 50 ,
                    "order" : [1 , "asc"] ,
                    stateSave: true ,
                });
            }
        }
    });
    
    return false; 
}

// Load telegram settings
function a_load_telegram_settings(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=load_telegram_settings&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                
                $('.telegram_settings').append(response);
            }
        }
    });
    
    return false; 
}

// Load telegram settings
function a_load_advanced_settings(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=load_advanced_settings&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $('#bot_settings').append(response);
            }
        }
    });
    
    return false; 
}

// Load telegram settings
function a_load_logbook(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=load_logbook&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $('.logbook').append(response);
                $('#logbook_table').DataTable({
                    stateSave: true ,
                    "pageLength": 25 ,
                    "order" : [0 , "desc"] ,
                });
                $('#info_table').DataTable({
                    stateSave: true ,
                    "pageLength": 25 ,
                    "order" : [0 , "desc"] ,
                });
            }
        }
    });
    
    return false; 
}

// Load debug log
function a_load_debug_log()
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=load_debuglog',
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $('.logbook').append(response);
                $('#debug_table').DataTable({
                    stateSave: true ,
                    "pageLength": 50 ,
                    "order" : [0 , "desc"] ,
                });
            }
        }
    });
    
    return false; 
}


// Action to add an account
function a_change_bots()
{
    var change_bots_form = document.getElementById("change_bots");
    var fd = new FormData(change_bots_form);

    //alert(fd);

    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=change_bots',
        data: fd,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                });
            }
        }
    });
    
    return false;
}

function a_change_telegram_settings()
{
    var change_telegram_settings_form = document.getElementById("change_telegram_settings");
    var fd = new FormData(change_telegram_settings_form);

    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=change_telegram_settings',
        data: fd,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            alert(response);
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                }); 
            }
        }
    });
}


function a_sent_telegram_message(id)
{
    $.ajax({
        type: 'post',
        url: 'requesthandler.php?action=sent_telegram_msg&id=' + id,
        success: function (response) {
            if (response == 'ERROR_NOT_LOGGED_IN') {
                location.href = 'logout.php?response=incorrect_ajax_call';
            } else {
                $( "#dialog" ).dialog({
                    title : 'Notice',
                    autoOpen : true,
                    open: function() {
                        $(this).html(response);
                    } ,
                    close: function() {
                        //location.reload();
                }
                }); 
            }
        }
    });
    
    return false; 
}

// Link to Add account
$(document).on("click", '.add_account_link', function() { 
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.add_account').show();
});

// Link to Edit account
$(document).on("click", '.advanced_settings_link', function() { 
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.advanced_settings').show();
    $('#bot_settings').empty();
    $('#tv_alerts').empty();
});

// Link to Delete account
$(document).on("click", '.delete_account_link', function() { 
    var id = $(this).attr("id");
    confirm_res = confirm("Are you sure you want to delete this account?");
    if(confirm_res) {
       return a_delete_account(id);
    } 
});

// Link to Disable account
$(document).on("click", '.disable_account_link', function() { 
    var id = $(this).attr("id");
    confirm_res = confirm("Are you sure you want to disable this account?");
    if(confirm_res) {
       return a_disable_account(id);
    } 
});

// Link to Enable account
$(document).on("click", '.enable_account_link', function() { 
    var id = $(this).attr("id");
    confirm_res = confirm("Are you sure you want to enable this account?");
    if(confirm_res) {
       return a_enable_account(id);
    } 
});

// Link to update max active deals
$(document).on("change", '.mad_dropdown', function() { 
    var id = $(this).attr("id");
    var deals = $(this).val();

    return a_update_max_active_deals(id , deals);
});


// Link to update max active deals
$(document).on("change", '.mad_direction', function() { 
    var id = $(this).attr("id");
    var direction = $(this).val();

    return a_update_mad_direction(id , direction);
});

// Link to update bo size
$(document).on("change", '.bo_size', function() { 
    var id = $(this).attr("id");
    var size = $(this).val();

    confirm_res = confirm("Are you sure you want to update the BO size to " + size + "?");
    if(confirm_res) {
        return a_update_bo_size(id , size);
    } 


});

// Link to update leverage
$(document).on("change", '.leverage', function() { 
    var id = $(this).attr("id");
    var leverage = $(this).val();

    confirm_res = confirm("Are you sure you want to update the Leverage to " + leverage + "x?");
    if(confirm_res) {
         return a_update_leverage(id , leverage);
    } 


});

// Link to update leverage
$(document).on("change", '.leverage_mode', function() { 
    var id = $(this).attr("id");
    var leverage_mode = $(this).val();

    confirm_res = confirm("Are you sure you want to update the Leverage mode to " + leverage_mode + "?");
    if(confirm_res) {
         return a_update_leverage_mode(id , leverage_mode);
    } 


});

// Link to update hedge_mode
$(document).on("change", '.hedge_mode', function() { 
    var id = $(this).attr("id");
    var hedge_mode = $(this).val();

    confirm_res = confirm("Change hedge mode?");
    if(confirm_res) {
         return a_update_hedge(id , hedge_mode);
    } 
});

// Link to update stoploss setting
$(document).on("change", '.use_stoploss', function() { 
    var id = $(this).attr("id");
    var use_stoploss = $(this).val();

    confirm_res = confirm("Change stop loss setting?");
    if(confirm_res) {
         return a_update_stoploss(id , use_stoploss);
    } 
});

// Link to update hedge_mode
$(document).on("change", '.stoploss_percentage', function() { 
    var id = $(this).attr("id");
    var percentage = $(this).val();

    confirm_res = confirm("Change S/L percentage?");
    if(confirm_res) {
         return a_update_stoploss_percentage(id , percentage);
    } 
});

// Link to update hedge_mode
$(document).on("change", '.away_mode', function() { 
    var id = $(this).attr("id");
    var status = $(this).val();

    confirm_res = confirm("Change away mode status?");
    if(confirm_res) {
         return a_update_away_mode(id , status);
    } 
});

// Link to update hedge_mode
$(document).on("change", '.away_closure', function() { 
    var id = $(this).attr("id");
    var percentage = $(this).val();

    confirm_res = confirm("Change close %?");
    if(confirm_res) {
         return a_update_away_closure(id , percentage);
    } 
});

// Link to update hedge_mode
$(document).on("change", '.away_stoploss', function() { 
    var id = $(this).attr("id");
    var percentage = $(this).val();

    confirm_res = confirm("Change S/L percentage for away mode?");
    if(confirm_res) {
         return a_update_away_stoploss(id , percentage);
    } 
});

// Link to update max active deals
$(document).on("change", '.active', function() { 
    var id = $(this).attr("id");
    var status = $(this).val();

    return a_update_active(id , status);
});

// Submit button manage bots
$(document).on("click", '.submit_mb', function() { 
    alert('Update bot settings. This may take a while , don\'t close your browser!');
});

// Link to Manage bots
$(document).on("click", '.manage_bots_link', function() { 
    var id = $(this).attr("id");
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.manage_bots').empty();
    $('.manage_bots').show();

  
    return a_load_bots(id);
});

// Link to view trading_view alerts
$(document).on("click", '.tv_alerts_link', function() { 
    var id = $(this).attr("id");
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.tv_alerts').empty();
    $('.tv_alerts').show();
    
    return a_load_tv_alerts(id);
});

// Link to view Telegram setting
$(document).on("click", '.telegram_settings_link', function() { 
    var id = $(this).attr("id");
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.telegram_settings').empty();
    $('.telegram_settings').show();
    
    return a_load_telegram_settings(id);
});

// Link to test_telegram msg
$(document).on("click", '.test_message_link', function() { 
    var id = $(this).attr("id");
    
    return a_sent_telegram_message(id);
});

// Link to edit account msg
$(document).on("click", '.advanced_settings_link', function() { 
    var id = $(this).attr("id");
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    //$('.advanced_settings').empty();
    $('.advanced_settings').show();

    a_load_tv_alerts(id);
    return a_load_advanced_settings(id);
});

// Link to logbook msg
$(document).on("click", '.logbook_link', function() { 
    var id = $(this).attr("id");
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.logbook').empty();
    $('.logbook').show();

    return a_load_logbook(id);
});

// Link to logbook msg
$(document).on("click", '.debug_log_link', function() { 
    var id = $(this).attr("id");
    $('.hide').hide();
    $('.home').show();
    $('.workspace').show();
    $('.logbook').empty();
    $('.logbook').show();

    return a_load_debug_log();
});

// Link to Home
$(document).on("click", '.back_home_link', function() { 
    $('.home').hide();
    location.reload();
});


// JQuery for changes of differnt general bot settings , may be easier but jquery isn't my strongest asset ;)
$(document).on("change mousedown", '.so_type_all', function() { 
    $('.so_type_bots').val($(this).val());
});

$(document).on("change", '.max_so_all', function() { 
    $('.max_so_bots').val($(this).val());
});

$(document).on("change", '.act_so_all', function() { 
    $('.act_so_bots').val($(this).val());
});

$(document).on("change mousedown", '.size_type_all', function() { 
    $('.size_type_bots').val($(this).val());
});

$(document).on("change", '.bo_size_all', function() { 
    $('.bo_size_bots').val($(this).val());
});

$(document).on("change", '.so_size_all', function() { 
    $('.so_size_bots').val($(this).val());
});

$(document).on("change", '.so_perc_all', function() { 
    $('.so_perc_bots').val($(this).val());
});

$(document).on("change", '.so_volume_all', function() { 
    $('.so_volume_bots').val($(this).val());
});

$(document).on("change", '.so_step_all', function() { 
    $('.so_step_bots').val($(this).val());
});

$(document).on("change", '.tp_all', function() { 
    $('.tp_bots').val($(this).val());
});

$(document).on("change", '.ttp_all', function() { 
    $('.ttp_bots').val($(this).val());
});

$(document).on("change", '.ttp_deviation_all', function() { 
    $('.ttp_deviation_bots').val($(this).val());
});

$(document).on("change", '.cooldown_all', function() { 
    $('.cooldown_bots').val($(this).val());
});

$(document).on("change", '.lev_type_all', function() { 
    $('.lev_type_bots').val($(this).val());
});

$(document).on("change", '.lev_value_all', function() { 
    $('.lev_value_bots').val($(this).val());
});

$(document).on("change", '.is_enabled_all', function() { 
    $('.is_enabled_bots').val($(this).val());
});

// Form validatipon

// Restricts input for the set of matched elements to the given inputFilter function.
(function($) {
    $.fn.inputFilter = function(inputFilter) {
      return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
        if (inputFilter(this.value)) {
          this.oldValue = this.value;
          this.oldSelectionStart = this.selectionStart;
          this.oldSelectionEnd = this.selectionEnd;
        } else if (this.hasOwnProperty("oldValue")) {
          this.value = this.oldValue;
          this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
        } else {
          this.value = "";
        }
      });
    };
  }(jQuery));










