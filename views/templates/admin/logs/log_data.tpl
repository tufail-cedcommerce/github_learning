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
<span class="btn-group-action">
               <button style="display:none;" type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#module-modal-cedelkjopnordic{$id|escape:'htmlall': 'UTF-8'}" id="module-modal-cedelkjopnordic-button{$id|escape:'htmlall': 'UTF-8'}">
				<i class="material-icons">edit</i>
            </button>
              <button  type="button" class="btn btn-info btn-lg" onclick="prettyPrint('{$id|escape:'htmlall': 'UTF-8'}');"><i class="material-icons">remove_red_eye</i></button>
</span>
<div id="module-modal-cedelkjopnordic{$id|escape:'htmlall': 'UTF-8'}" class="modal  modal-vcenter fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title module-modal-title">Log Info</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {if $data}
                                <textarea rows="25" name="feed_content{$id|escape:'htmlall': 'UTF-8'}" class="form-control" id="feed_content{$id|escape:'htmlall': 'UTF-8'}">{$data|escape:'htmlall': 'UTF-8'}</textarea>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function prettyPrint(row_id) {
        var ugly = document.getElementById('feed_content'+row_id).value;
        var obj = JSON.parse(ugly);
        var pretty = JSON.stringify(obj, undefined, 4);
        document.getElementById('feed_content'+row_id).value = pretty;
        document.getElementById('module-modal-cedelkjopnordic-button'+row_id).click();
    }
</script>
