<script type="application/javascript">
     function getAttributeSynced(button_object, profile_id, shop_id, category_id, token) {
        button_object.disabled=true;
        $.ajax({
            type: "POST",
            url: 'ajax-tab.php',
            data: {
                ajax: true,
                controller: 'AdminCedElkjopnordicProfile',
                action: 'getAttributeSynced',
                token: token,
                profile_id:profile_id,
                category_id:category_id,
                shop_id:shop_id,
            },
            success: function(response) {
                button_object.disabled=false;
                var response = JSON.parse(response);
                if (response.success && response.message) {
                    alert(response.message);
                } else {
                    alert('Failed to load feed data.');
                }
            },
            statusCode: {
                500: function(xhr) {
                    if (window.console) console.log(xhr.responseText);
                },
                400: function (response) {
                    if (window.console) console.log(xhr.responseText);
                },
                404: function (response) {
                    if (window.console) console.log(xhr.responseText);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                if (window.console) console.log(xhr.responseText);
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

            },
        });
    }

    function getOptionSynced(button_object, profile_id, shop_id, category_id,token) {
        button_object.disabled=true;
        $.ajax({
            type: "POST",
            url: 'ajax-tab.php',
            data: {
                ajax: true,
                controller: 'AdminCedElkjopnordicProfile',
                action: 'getOptionSynced',
                token: token,
                profile_id:profile_id,
                category_id:category_id,
                shop_id:shop_id,
            },
            success: function(response) {
                button_object.disabled=false;
                var response = JSON.parse(response);
                if (response.success && response.message) {
                    alert(response.message);
                } else {
                    alert('Failed to load feed data.');
                }
            },
            statusCode: {
                500: function(xhr) {
                    if (window.console) console.log(xhr.responseText);
                },
                400: function (response) {
                    if (window.console) console.log(xhr.responseText);
                },
                404: function (response) {
                    if (window.console) console.log(xhr.responseText);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                if (window.console) console.log(xhr.responseText);
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

            },
        });
    }
    </script>
