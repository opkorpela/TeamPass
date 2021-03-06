<?php
/**
 * @file          find.load.php
 * @author        Nils Laumaillé
 * @version       2.1.27
 * @copyright     (c) 2009-2018 Nils Laumaillé
 * @licensing     GNU GPL-3.0
 * @link          http://www.teampass.net
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if (!isset($_SESSION['CPM']) || $_SESSION['CPM'] != 1) {
    die('Hacking attempt...');
}

$var['hidden_asterisk'] = '<i class="fa fa-eye fa-border fa-sm tip" title="'.$LANG['show_password'].'"></i>&nbsp;&nbsp;<i class="fa fa-asterisk"></i>&nbsp;<i class="fa fa-asterisk"></i>&nbsp;<i class="fa fa-asterisk"></i>&nbsp;<i class="fa fa-asterisk"></i>&nbsp;<i class="fa fa-asterisk"></i>';

?>

<script type="text/javascript">
//<![CDATA[
/*
* Copying an item from find page
*/
//###########
//## FUNCTION : prepare copy item dialogbox
//###########
function copy_item(item_id)
{
    $('#id_selected_item').val(item_id);

    $.post(
        "sources/items.queries.php",
        {
            type    : "refresh_visible_folders",
            key        : "<?php echo $_SESSION['key']; ?>"
        },
        function(data) {
            data = prepareExchangedData(data , "decode", "<?php echo $_SESSION['key']; ?>");
            $("#copy_in_folder").find('option').remove().end().append(data.selectFullVisibleFoldersOptions);
            $('#div_copy_item_to_folder').dialog('open');
        }
    );

}

$("#div_copy_item_to_folder").dialog({
      bgiframe: true,
      modal: true,
      autoOpen: false,
      width: 400,
      height: 200,
      title: "<?php echo $LANG['item_menu_copy_elem']; ?>",
      buttons: {
          "<?php echo $LANG['ok']; ?>": function() {
              //Send query
                $.post(
                    "sources/items.queries.php",
                    {
                        type    : "copy_item",
                        item_id : $('#id_selected_item').val(),
                        folder_id : $('#copy_in_folder').val(),
                        key        : "<?php echo $_SESSION['key']; ?>"
                    },
                    function(data) {
                        //check if format error
                        if (data[0].error !== "") {
                            $("#copy_item_to_folder_show_error").html(data[1].error_text).show();
                        }
                        //if OK
                        if (data[0].status == "ok") {
                            $("#div_dialog_message_text").html("<?php echo $LANG['alert_message_done']; ?>");
                            $("#div_dialog_message").dialog('open');
                            $("#div_copy_item_to_folder").dialog('close');
                        }
                    },
                    "json"
               );
          },
          "<?php echo $LANG['cancel_button']; ?>": function() {
              $("#copy_item_to_folder_show_error").html("").hide();
              $(this).dialog('close');
          }
      }
  });

/*
* Open a dialogbox with item data
*/
function see_item(item_id, personalItem, folder_id, expired_item, restricted)
{
    $('#id_selected_item').val(item_id);
    $("#personalItem").val(personalItem);
    $("#expired_item").val(expired_item);
    $("#restricted_item").val(restricted);
    $("#folder_id_of_item").val(folder_id);
    $('#div_item_data').dialog('open');
}

// show password during longpress
var mouseStillDown = false;
$('#div_item_data_text').on('mousedown', '.unhide_masked_data', function(event) {
    mouseStillDown = true;
     showPwdContinuous($(this).attr('id'));
}).on('mouseup', '.unhide_masked_data', function(event) {
     mouseStillDown = false;
}).on('mouseleave', '.unhide_masked_data', function(event) {
     mouseStillDown = false;
});
var showPwdContinuous = function(elem_id){
    if(mouseStillDown){
        $('#'+elem_id).html('<span style="cursor:none;">' + $('#h'+elem_id).html().replace(/\n/g,"<br>") + '</span>');
        setTimeout("showPwdContinuous('"+elem_id+"')", 50);
        // log password is shown
        if (elem_id === "id_pw" && $("#pw_shown").val() == "0") {
            // log passd show
            var data = {
                "id" : $('#id_selected_item').val(),
                "label" : $('#item_label').text(),
                "user_id" : "<?php echo $_SESSION['user_id']; ?>",
                "action" : 'at_password_shown',
                "login" : "<?php echo $_SESSION['login']; ?>"
            };

            $.post(
                "sources/items.logs.php",
                {
                    type    : "log_action_on_item",
                    data :  prepareExchangedData(JSON.stringify(data), "encode", "<?php echo $_SESSION['key']; ?>"),
                    key        : "<?php echo $_SESSION['key']; ?>"
                }
            );

            $("#pw_shown").val("1");
        }
    } else {
        $('#'+elem_id).html('<?php echo $var['hidden_asterisk']; ?>');
        $('.tip').tooltipster({multiple: true});
    }
};

$("#div_item_data").dialog({
      bgiframe: true,
      modal: true,
      autoOpen: false,
      width: 450,
      height: 220,
      title: "<?php echo $LANG['see_item_title']; ?>",
      open:
        function(event, ui) {
            $("#div_item_data_show_error").html("<?php echo $LANG['admin_info_loading']; ?>").show();

            // prepare data
            var data = {
                "id" : $('#id_selected_item').val(),
                "folder_id" : $('#folder_id_of_item').val(),
                "salt_key_required" : $('#personalItem').val(),
                "salt_key_set" : $('#personal_sk_set').val(),
                "expired_item" : $("#expired_item").val(),
                "restricted" : $("#restricted_item").val(),
                "page" : "find"
            };

            $.post(
                "sources/items.queries.php",
                {
                    type :  "show_details_item",
                    data :  prepareExchangedData(JSON.stringify(data), "encode", "<?php echo $_SESSION['key']; ?>"),
                    key  :  "<?php echo $_SESSION['key']; ?>"
                },
                function(data) {
                    //decrypt data
                    data = prepareExchangedData(data, "decode", "<?php echo $_SESSION['key']; ?>");
                    var return_html = "";
                    if (data.show_detail_option != "0" || data.show_details == 0) {
                        //item expired
                        return_html = "<?php echo $LANG['not_allowed_to_see_pw_is_expired']; ?>";
                    } else if (data.show_details == "0") {
                        //Admin cannot see Item
                        return_html = "<?php echo $LANG['not_allowed_to_see_pw']; ?>";
                    } else {
                        return_html = "<table>"+
                            "<tr><td valign='top' class='td_title'><span class='fa fa-caret-right'></span>&nbsp;<?php echo $LANG['label']; ?> :</td><td style='font-style:italic;display:inline;' id='item_label'>"+data.label+"</td></tr>"+
                            "<tr><td valign='top' class='td_title'><span class='fa fa-caret-right'></span>&nbsp;<?php echo $LANG['description']; ?> :</td><td style='font-style:italic;display:inline;'>"+data.description+"</td></tr>"+
                            "<tr><td valign='top' class='td_title'><span class='fa fa-caret-right'></span>&nbsp;<?php echo $LANG['pw']; ?> :</td>"+
                            "<td style='font-style:italic;display:inline;'>"+
                            '<div id="id_pw" class="unhide_masked_data" style="float:left; cursor:pointer; width:300px;"></div>'+
                            '<div id="hid_pw" class="hidden"></div>'+
                            '<input type="hidden" id="pw_shown" value="0" />'+
                            "</td></tr>"+
                            "<tr><td valign='top' class='td_title'><span class='fa fa-caret-right'></span>&nbsp;<?php echo $LANG['index_login']; ?> :</td><td style='font-style:italic;display:inline;'>"+data.login+"</td></tr>"+
                            "<tr><td valign='top' class='td_title'><span class='fa fa-caret-right'></span>&nbsp;<?php echo $LANG['url']; ?> :</td><td style='font-style:italic;display:inline;'>"+data.url+"</td></tr>"+
                        "</table>";
                    }
                    $("#div_item_data_show_error").html("").hide();
                    $("#div_item_data_text").html(return_html);
                    $('#id_pw').html('<?php echo $var['hidden_asterisk']; ?>');
                    $('#hid_pw').html(unsanitizeString(data.pw));
                }
           );
        }
      ,
      close:
        function(event, ui) {
            $("#div_item_data_text").html("");
        }
        ,
      buttons: {
          "<?php echo $LANG['ok']; ?>": function() {
              $(this).dialog('close');
          }
      }
  });

$(function() {
    //Launch the datatables pluggin
    oTable = $("#t_items").dataTable({
        "aaSorting": [[ 1, "asc" ]],
        "sPaginationType": "full_numbers",
        "bProcessing": true,
        "bServerSide": true,
        "sAjaxSource": "<?php echo $SETTINGS['cpassman_url']; ?>/sources/find.queries.php",
        "bJQueryUI": true,
        "oLanguage": {
            "sUrl": "<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $_SESSION['user_language']; ?>.txt"
        },
        "fnInitComplete": function() {
            $("#find_page input").focus();
        },
        "columns": [
            {"width": "10%", className: "dt-body-left"},
            {"width": "15%"},
            {"width": "10%"},
            {"width": "25%"},
            {"width": "10%"},
            {"width": "15%"},
            {"width": "15%"}
        ]
    });




    /*
    **
    */
    $("#div_mass_op").dialog({
        bgiframe: true,
        modal: true,
        autoOpen: false,
        width: 500,
        height: 400,
        title: "<?php echo $LANG['mass_operation']; ?>",
        open: function() {
            var html = sel_items = sel_items_txt = item_id = '';

            $("#div_mass_op_msg").html("").hide();

            // selected items
            $(".mass_op_cb:checkbox:checked").each(function () {
                item_id = $(this).attr('id').split('-')[1] ;
                sel_items += item_id + ";";
                if (sel_items_txt === "") {
                    sel_items_txt = '<li>' + $("#item_label-"+item_id).text() + '</li>';
                } else {
                    sel_items_txt += "<li>" + $("#item_label-"+item_id).text() + '</li>';
                }
            });

            // prepare display
            if ($("#div_mass_op").data('action') === "move") {
                html = '<?php echo $LANG["you_decided_to_move_items"]; ?>: ' +
                '<div><ul>' + sel_items_txt + '</ul></div>';
                var folder_options = '';

                // get list of folders
                $.post(
                    "sources/folders.queries.php",
                    {
                        type    : "get_list_of_folders",
                        key     : "<?php echo $_SESSION['key']; ?>"
                    },
                    function(data) {
                        $("#div_loading").hide();
                        // check/uncheck checkbox
                        if (data[0].list_folders !== "") {
                            var tmp = data[0].list_folders.split(";");
                            for (var i = tmp.length - 1; i >= 0; i--) {
                                folder_options += tmp[i];
                            }
                        }

                        // destination folder
                         html += '<div style=""><?php echo $LANG['import_keepass_to_folder']; ?>:&nbsp;&nbsp;' +
                         '<select id="mass_move_destination_folder_id">' + data[0].list_folders + '</select>' +
                         '</div>';

                        //display to user
                        $("#div_mass_html").html(html);
                    },
                    "json"
                );
            } else if ($("#div_mass_op").data('action') === "delete") {
                html = '<?php echo $LANG['you_decided_to_delete_items']; ?>: ' +
                '<div><ul>' + sel_items_txt + '</ul></div>' +
                '<div style="padding:10px;" class="ui-corner-all ui-state-error"><span class="fa fa-warning fa-lg"></span>&nbsp;<?php echo $LANG['confirm_deletion']; ?></div>';

                $("#div_mass_html").html(html);
            }

        },
        buttons: {
            "<?php echo $LANG['ok']; ?>": function() {
                $("#div_mass_op_msg")
                    .addClass("ui-state-highlight")
                    .html('<span class="fa fa-cog fa-spin fa-lg"></span>&nbsp;<?php echo $LANG['please_wait']; ?>')
                    .show();

                var sel_items = '';

                // selected items
                $(".mass_op_cb:checkbox:checked").each(function () {
                    sel_items += $(this).attr('id').split('-')[1] + ";";
                });

                if (sel_items === "") {
                    $("#div_mass_op_msg")
                        .addClass("ui-state-error")
                        .html('<span class="fa fa-warning fa-lg"></span>&nbsp;<?php echo $LANG['must_select_items']; ?>')
                        .show().delay(2000).fadeOut(1000);
                    return false;
                }

                if ($("#div_mass_op").data('action') === "move") {
                // MASS MOVE

                    //Send query
                    $.post(
                        "sources/items.queries.php",
                        {
                            type        : "mass_move_items",
                            item_ids    : sel_items,
                            folder_id   : $("#mass_move_destination_folder_id").val(),
                            key         : "<?php echo $_SESSION['key']; ?>"
                        },
                        function(data) {
                            //check if format error
                            if (data[0].error !== "") {
                                $("#div_mass_op_msg").html(data[1].error_text).show();
                            }
                            //if OK
                            if (data[0].status == "ok") {
                                //reload search
                                oTable.api().ajax.reload();

                                $("#main_info_box_text").html("<?php echo $LANG['alert_message_done']; ?>");
                                    $("#main_info_box").show().position({
                                        my: "center",
                                        at: "center top+75",
                                        of: "#top"
                                    });
                                    setTimeout(function(){$("#main_info_box").effect( "fade", "slow" );}, 1000);

                                // show finished
                                $("#div_mass_op").dialog("close");
                            }
                        },
                        "json"
                    );
                } else if ($("#div_mass_op").data('action') === "delete") {
                // MASS DELETE

                    //Send query
                    $.post(
                        "sources/items.queries.php",
                        {
                            type        : "mass_delete_items",
                            item_ids    : sel_items,
                            key         : "<?php echo $_SESSION['key']; ?>"
                        },
                        function(data) {
                            //check if format error
                            if (data[0].error !== "") {
                                $("#div_mass_op_msg").html(data[0].error).show();
                            }
                            //if OK
                            if (data[0].status == "ok") {
                                //reload search
                                oTable.api().ajax.reload();

                                $("#main_info_box_text").html("<?php echo $LANG['alert_message_done']; ?>");
                                    $("#main_info_box").show().position({
                                        my: "center",
                                        at: "center top+75",
                                        of: "#top"
                                    });
                                    setTimeout(function(){$("#main_info_box").effect( "fade", "slow" );}, 1000);

                                // show finished
                                $("#div_mass_op").dialog("close");
                            }
                        },
                        "json"
                    );
                }
            },
            "<?php echo $LANG['cancel_button']; ?>": function() {
                $(this).dialog('close');
            }
        }
    });
});
//]]>
</script>
