<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-user"></i> {l s='Profile Selector' mod='cedelkjopnordic'}
    </div>
    <div class="panel-body">
        <div class="form-group row">
            <div class="col-lg-4">
                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="Current Elkjopnordic Profile" data-original-title="">
                    {l s='Elkjopnordic Profile ' mod='cedelkjopnordic'}
                </span>
            </div>    
            <div class="col-lg-8">
                <select name="id_elkjopnordic_profile" id="id_elkjopnordic_profile" onchange="changeElkjopnordicProfile()" class="livesearch">
                    <option value=""> {l s='Select Elkjopnordic Profile' mod='cedelkjopnordic'}</option>
                    {if isset($vprofiles) && !empty($vprofiles)}
                        {foreach $vprofiles as $vprofile}
                            <option
                                    {if isset($idCurrentProfile) && !empty($idCurrentProfile) && $idCurrentProfile == $vprofile['id']}
                                        selected="selected"
                                    {/if}
                                    value="{$vprofile['id']|escape:'htmlall':'UTF-8'}">
                                {$vprofile['title']|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    {/if}
                </select>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.5.1/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.5.1/chosen.min.css">

<script>
    function changeElkjopnordicProfile()
    {
        var url = '{$controllerUrl}';
       
       if($('#id_elkjopnordic_profile') && $('#id_elkjopnordic_profile').val())
        var x = location.hash;
        if(x){
         url = url.replace(x ,'');
         url = url + '&id_elkjopnordic_profile='+ $('#id_elkjopnordic_profile').val();
         url = url +x; 
        } else {
         url = url + '&id_elkjopnordic_profile='+ $('#id_elkjopnordic_profile').val();
        }
       

        window.location = url;
    }
    $(document).ready(function () {
        var url = '{$controllerUrl}';

        var idCurrentProfile = '{$idCurrentProfile|escape:'htmlall'}';
        if (idCurrentProfile != 'all' && isNaN(idCurrentProfile)) {
            var ProfileId = $('#id_elkjopnordic_profile').val();
            
            var x = location.hash;
        if(x){
         url = url.replace(x ,'');
         url = url + '&id_elkjopnordic_profile='+ ProfileId;
         url = url +x; 
        } else {
         url = url + '&id_elkjopnordic_profile='+ ProfileId;
        }
            window.location = url;
            }
            

    })
    window.onload = function () {
        $(".livesearch").chosen({
            'width' : "100%"
        });

    };
</script>
