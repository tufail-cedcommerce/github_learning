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

$(document).ready(function () {
    var checked = [];
    $('#elkjopnordic-overlay').hide();
    getElkjopnordicCategory(0, 1);
    $('#categories-treeview li input[type=checkbox]').each(function (d) {
        if (this.checked == true) {
            checked.push(d);
        }
    });
    $('#categories-treeview li input[type=checkbox]').on('change', function () {
        if (this.checked == false) {
            var res = confirm("If you remove category from profile all the products of this category will be removed from this profile " +
                "also the profile data will be removed from product like shipping data and attribute mapping." +
                " Do you really want to remove category from this profile ?");
            if (res == false) {
                this.checked = true;
                $(this).parent('span').addClass("tree-selected");
            }
        }
    });
    $("#uncheck-all-categories-treeview").on('click', function () {
        var res = confirm("If you remove category from profile all the products of this category will be removed from this profile " +
            "also the profile data will be removed from product like shipping data and attribute mapping." +
            " Do you really want to remove category from this profile ?");
        if (res == false) {
            $('#categories-treeview li input[type=checkbox]').each(function (d) {
                if (checked.indexOf(d) > -1) {
                    this.checked = true;
                    $(this).parent('span').addClass("tree-selected");
                }
            })
        }
    });

    togglePriceVariantAmount();

    $('#price-variant-type').change(function () {
        togglePriceVariantAmount();
    });

    function togglePriceVariantAmount()
    {
        console.log($('#price-variant-type').val());
        if ($('#price-variant-type').val() === '0' || $('#price-variant-type').val() === '1') {
            $('#price-variant-amount').parent().parent().hide();
        } else {
            $('#price-variant-amount').parent().parent().show();
        }
    }

});

function getElkjopnordicCategory(value, current_level)
{
    if (current_level >1 && !value) {
        document.getElementById("elkjopnordic_category_"+current_level).value = value;
        var children =  document.getElementById('elkjopnordic-category');
        var elementlengh = children.childElementCount;
        for (var i = current_level; i <= elementlengh; i++) {
            document.getElementById("select-container-level-"+i).remove();
        }
        return false;
    }
    $('#elkjopnordic-overlay').show();
    var query = $.ajax({
        type: 'POST',
        url: 'ajax-tab.php',
        data: {
            controller: 'AdminCedElkjopnordicProfile', /* better lowercase 'category' */
            ajax : true,
            action : 'getElkjopnordicCategory',
            elkjopnordic_profile_id: $("input[name=elkjopnordicProfileId]").val(),
            hierarchy: value,
            max_level: current_level,
            token : $('#back-elkjopnordic-profile-controller').attr('data-token')
        },
        success: function (res) {
            try {
                var data = JSON.parse(res);
                $('#elkjopnordic-overlay').hide();
                if (current_level == 1 && data.success == false && data.message != '') {
                    document.getElementById('elkjopnordic-category').innerHTML = data.message  ;
                }
                if (data.success == true) {
                    createNewCategoryLevel(current_level,data.message);
                    if (document.getElementById("elkjopnordic_category_"+current_level) &&
                        document.getElementById("elkjopnordic_category_"+current_level).value
                        && (data.success == true)) {
                        // console.log(document.getElementById("elkjopnordic_category_"+current_level).value);
                        // console.log(current_level+1);
                        getElkjopnordicCategory(document.getElementById("elkjopnordic_category_"+current_level).value,current_level+1);
                    }
                }
                if (data.success == false && data.message == '') {
                    getCategoryAttributes(value);
                }
            } catch (e) {
                $('#elkjopnordic-overlay').hide();
                console.log(e.message);
                if (document.getElementById('elkjopnordic-category')) {
                    document.getElementById('elkjopnordic-category').innerHTML = e.message  ;
                }
            }

        }
    });
}
function createNewCategoryLevel(level,array)
{
    var container_div;
    if (document.getElementById("select-container-level-"+level)) {
        container_div = document.getElementById("select-container-level-"+level);
    } else {
        container_div = document.createElement("div");
        container_div.id = "select-container-level-"+level;
    }
    container_div.style.padding = '10px';
    if ( array!='<h1>Unable to fetch category</h1>') {
        var fieldset_wrapper = document.getElementById("elkjopnordic-category");
        if (fieldset_wrapper) {
            fieldset_wrapper.appendChild(container_div);
            var selectList = document.createElement("select");
            selectList.id = "elkjopnordic_category_"+level;
            selectList.name = "elkjopnordicCategory[level_"+level+"]";
            selectList.className = 'required-entry input-text required-entry';
            container_div.innerHTML = "";
            container_div.appendChild(selectList);
            selectList.innerHTML = array;
            $("#elkjopnordic_category_"+level).on('change', function () {
                getElkjopnordicCategory(this.value,level+1);
            });
        }
    }
    var children =  document.getElementById('elkjopnordic-category');
    if (children) {
        var elementlengh = children.childElementCount;
        for (var i = level+1; i <= elementlengh; i++) {
            document.getElementById("select-container-level-"+i).remove();
        }
    }

}

function closeMessage()
{
    $("#error-message").hide();
}
function submitElkjopnordicProfile(e)
{
     var profileCode = document.getElementById('profile-title').value;

     var profileManufacturer = document.getElementById('profile-manufacturer').value;

    var storeCategories = [];

    $('#categories-treeview li input[type=checkbox]').each(function (d) {
        if (this.checked == true) {
            storeCategories.push(d);
        }
    });
    var valid = true;
    var elkjopnordicCategory = [];
    $('#elkjopnordic-category div select').each(function (d) {
        if (this.value != '') {
            elkjopnordicCategory.push(d);
        }
    });
    var errorMessage = [];
    if (!profileCode) {
        valid = false;
        errorMessage.push('Please Enter Valid Profile Name');
    }

    if (elkjopnordicCategory.length < 1) {
        valid = false;

        errorMessage.push('Please Map Elkjopnordic Category');
    }


    var requiredValid = true;
    $('form#elkjopnordic-profile-form').find('select,input').each(function () {
        if ($(this).prop('required')) {
              var reqId = this.id;
            if (!$('#'+reqId).val()) {
                requiredValid = false;
            }
        }
    });

    if (requiredValid == false) {
        valid = false;
        errorMessage.push('Please Fill All Required Fields');
    }


    if (valid == false) {
        e.preventDefault();
        $("#error-message").show();
        $('html,body').scrollTop(0);
        var index = 1;
        var msg = '';
        for (var m in errorMessage) {
            msg += index + ': ' + errorMessage[m] + '<br>';
            index ++;
        }
        $("#default-error-message-text").html(msg);
        return false;
    } else {
        errorMessage = '';
        $("#error-message").hide();
        $("#default-error-message-text").html(errorMessage);
        return true;
    }

    return false;
}


function numberValidate(event, el)
{
    var attr = el.placeholder;
    const charCode = (event.which) ? event.which : event.keyCode;
    if (charCode !== 46 && charCode!== 8 && charCode!==8 && (charCode < 48 || charCode > 57)) {
        event.preventDefault();
        return false;
    }
    return true;
}

function getCategoryAttributes(catId)
{
    if (catId != '') {
        $.ajax({
            type: 'POST',
            url: 'ajax-tab.php',
            data: {
                controller: 'AdminCedElkjopnordicProfile', /* better lowercase 'category' */
                ajax : true,
                action : 'updateElkjopnordicCategoryAttributes',
                elkjopnordic_profile_id: $("input[name=elkjopnordicProfileId]").val(),
                hierarchy: catId,
                token : $('#back-elkjopnordic-profile-controller').attr('data-token')
            },
            success: function (res) {
                console.log(res);
                try {
                    var data = JSON.parse(res);
                    if (data.success == true) {
                        document.getElementById('profileAttributes').innerHTML = data.message  ;
                    }
                } catch (e) {
                    document.getElementById('profileAttributes').innerHTML = e.message  ;
                }

            }
        });
    }
}


