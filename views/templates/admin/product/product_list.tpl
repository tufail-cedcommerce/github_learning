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
 * @package   CedElkjopnordic
 */
-->
<div id="myModal" class="modal">
  <div class="modal-content">
    <span class="close" id="close_model">&times;</span>
    <div id="popup_content_celkjopnordic"> Loading.......</div>
  </div>
</div>
<script type="text/javascript">
function viewDetails(product_id) {
  modal.style.display = "block";
  var url = 'index.php?controller=AdminCelkjopnordicProducts&method=viewDetails&token={$token|escape:'htmlall':'UTF-8'}';
    $.ajax({
      type: "POST",
      url: url,
      data: { 'product_id':product_id },
      success: function(response){
        var response = JSON.parse(response);

        if(response){
          if(response.success){
            if(response.message){
              var html= '';
              var response = response.message;
              $.each(response ,function(key,value){
                if (typeof value == 'object') {
                  var inner_html='';
                  $.each(value ,function(k,v){
                    inner_html +='<p class="left" >'+k+' : '+v+'</p>';
                  });
                  html +='<tr><td class="left" >'+key+'</td><td class="left" >'+inner_html+'</td><tr>';
                } else {
                  html +='<tr><td class="left" >'+key+'</td><td class="left" >'+value+'</td><tr>';
                }
              });
              $("#popup_content_celkjopnordic").html(' Response : <table class="table">'+html+'</table>');

            }
          } else if(response.message){

              var html= '<table class="table">';
              var response = response.message;
              if (typeof response =='object') {
                $.each(response ,function(key,value){
                  html +='<tr><td><a href="#" onclick=viewDetailsBySku('+'"'+value+'"'+'); >'+value+'</a><td></tr>';
              });
               html += '</table>';
              }
              $("#popup_content_celkjopnordic").html(html);
            } else {
            var html= '';
            var response = response.message;
            if (typeof response =='object') {
              $.each(response[0] ,function(key,value){
                html +='<tr><td class="left" >'+key+'</td><td class="left" >'+value+'</td><tr>';
              });
              $("#popup_content_celkjopnordic").html(' Error : <table class="list">'+html+'</table>');
            } else {
                html +='<tr><td class="left" >Message&nbsp;&nbsp;</td><td class="left" >'+response+'</td><tr>';
              $("#popup_content_celkjopnordic").html('Error : <table class="table">'+html+'</table>');
            }


          }
        }
      }
      ,
      statusCode: {
      500: function(xhr) {
          if(window.console) console.log(xhr.responseText);
        },
      400: function (response) {
         alert('<span style="color:Red;">Error While Uploading Please Check</span>');
      },
      404: function (response) {

        alert('<span style="color:Red;">Error While Uploading Please Check</span>');
      }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        if(window.console) console.log(xhr.responseText);
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

    },
  });
}
function viewDetailsBySku(sku) {
  $("#popup_content_celkjopnordic").html('Loading.....');
  modal.style.display = "block";
  var url = 'index.php?controller=AdminCelkjopnordicProducts&method=viewDetailsBySku&token={$token|escape:'htmlall':'UTF-8'}';
    $.ajax({
      type: "POST",
      url: url,
      data: { 'sku':sku },
      success: function(response){
        var response = JSON.parse(response);
        console.log(response);
        if(response){
          if(response.success){
            if(response.message){
              var html= '';
              var response = response.message;
              $.each(response ,function(key,value){
                if (typeof value == 'object') {
                  var inner_html='';
                  $.each(value ,function(k,v){
                    inner_html +='<p class="left" >'+k+' : '+v+'</p>';
                  });
                  html +='<tr><td class="left" >'+key+'</td><td class="left" >'+inner_html+'</td><tr>';
                } else {
                  html +='<tr><td class="left" >'+key+'</td><td class="left" >'+value+'</td><tr>';
                }
              });
              $("#popup_content_celkjopnordic").html(' Response : <table class="table">'+html+'</table>');

            }
          } else {
            var html= '';
            var response = response.message;
            if (typeof response =='object') {
              $.each(response[0] ,function(key,value){
                html +='<tr><td class="left" >'+key+'</td><td class="left" >'+value+'</td><tr>';
              });
              $("#popup_content_celkjopnordic").html(' Error : <table class="list">'+html+'</table>');
            } else {
                html +='<tr><td class="left" >Message&nbsp;&nbsp;</td><td class="left" >'+response+'</td><tr>';
              $("#popup_content_celkjopnordic").html('Error : <table class="table">'+html+'</table>');
            }


          }
        }
      }
      ,
      statusCode: {
      500: function(xhr) {
          if(window.console) console.log(xhr.responseText);
        },
      400: function (response) {
         alert('<span style="color:Red;">Error While Uploading Please Check</span>');
      },
      404: function (response) {

        alert('<span style="color:Red;">Error While Uploading Please Check</span>');
      }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        if(window.console) console.log(xhr.responseText);
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

    },
  });
}
//--></script>
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
    width: 60%; /* Could be more or less, depending on screen size */
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
<script type="text/javascript">
var modal = document.getElementById('myModal');
var span = document.getElementById("close_model");
span.onclick = function() {
    modal.style.display = "none";
    $("#popup_content_celkjopnordic").html('Loading........');
}
</script>