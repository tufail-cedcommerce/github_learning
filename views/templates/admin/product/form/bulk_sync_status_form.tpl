<!--
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE(http://cedcommerce.com/)
 * @license   http://cedcommerce.com/license-agreement.txt
 * @category  Ced
 * @package   cedelkjopnordic
 */
-->

<div class="panel">
    <h3><i class="icon-tag"></i> {l s='Walmart MX Bulk Product Status Sync' mod='cedelkjopnordic'}</h3>

    <div class="row">
        <div class="buttons"><button id="cedelkjopnordicBulkSync" data-token="{$token|escape:'htmlall':'UTF-8'}" class="btn btn-primary" onclick="processReport();">Process Product Sync</button></div>
    </div>
    <div class="row">
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close" id="close_model">&times;</span>
                <div id="popup_content_loader"><p>Please wait processing Product........</p><p>This will take time as Product is large</p></div>
            </div>
        </div>
        <ol id="progress" style="display: initial;">
        </ol>
    </div>
</div>
<script type="text/javascript">
    var modal = document.getElementById('myModal');
    var span = document.getElementById("close_model");
    span.onclick = function() {
        modal.style.display = "none";
    }
</script>
<script type="text/javascript">
    var report_data = '{$update_array|escape:'mail':'UTF-8'}';
    var chunklimit = 10;
    report_data = JSON.parse(report_data);
    var chunk_array=[];
    $.each(report_data, function(key ,value){
        chunk_array.push(value);
    });
    chunked_array =[];
    while(chunk_array.length){
        chunked_array.push(chunk_array.splice(0,chunklimit));
    }
    chunked_array.reverse();
    function processReport() {
        var url = '{$controllerUrl|escape:'htmlall':'UTF-8'}';
        var clen=chunked_array.length;
        if(clen){
            modal.style.display = "block";
            sendUpdateRequest(chunked_array,url+'&is_ajax=1');
        }
        else
            $("#progress").append('<li class="alert alert-info" > No Report Recieved for upload products. </li>');
    }
    function sendUpdateRequest(chunked_array,url){
        var len=chunked_array.length-1;
        if(chunked_array[len]){
            $.ajax({
                type: "POST",
                url: 'ajax-tab.php',
                data: {
                    ajax: true,
                    controller: 'AdmincedelkjopnordicBulk',
                    action: 'bulkStatusSync',
                    token: $('#cedelkjopnordicBulkSync').attr('data-token'),
                    selected:chunked_array[len]
                },
                success: function(response){
                    response = JSON.parse(response);
                    if(response.success){
                        var obj = response;
                        if (obj.success && obj.success.length) {
                            var success_message = '';
                            for (var i in obj.success)
                            {
                                success_message+=obj.success[i] + '<br>';
                            }
                            $("#progress").append('<li class="alert alert-success" >'+success_message+'</li>');
                        }
                        if (obj.error && obj.error.length) {
                            var error_message = '';
                            for (var i in obj.error)
                            {
                                error_message+=obj.error[i] + '<br>';
                            }
                            $("#progress").append('<li class="alert alert-danger" >'+error_message+'</li>');
                        }
                        if(len!=0){
                            chunked_array.splice(len,1);
                            sendUpdateRequest(chunked_array,url);
                        }
//                        else {
//                            var error_message = '';
//                            for (var i in obj.error)
//                            {
//                                error_message+=obj.error[i];
//                            }
//                            $("#progress").append('<li class="alert alert-danger" ><p><b>Product Ids :'+chunked_array[len]+'</b></p></li>');
//                            if(len!=0){
//                                chunked_array.splice(len,1);
//                                sendUpdateRequest(chunked_array,url);
//                            }
//                        }
                    } else {
                        $("#progress").append('<li class="alert alert-danger">Error While Uploading Please Check</li>');
                    }
                    if (len==0) {
                        modal.style.display = "none";
                    }
                }
                ,
                statusCode: {
                    500: function(xhr) {
                        if(window.console) console.log(xhr.responseText);
                    },
                    400: function (response) {
                        $("#progress").append('<span style="color:Red;">Error While Uploading Please Check</span>');
                    },
                    404: function (response) {

                        $("#progress").append('<span style="color:Red;">Error While Uploading Please Check</span>');
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    if(window.console) console.log(xhr.responseText);
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

                },
            });

        } else {
            $("#progress").append('<li class="alert alert-info" > NO Report.</li>');
            modal.style.display = "none";
        }
    }
</script>
<style type="text/css">
    /* The Modal (background) */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content/Box */
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 20%; /* Could be more or less, depending on screen size */
    }

    /* The Close Button */
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>
