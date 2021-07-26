
<ul class="header-list component" style="align-items: center">
    <li class="dropdown" id="notification">
        <a class="dropdown-toggle" data-toggle="dropdown" style="cursor: pointer"><i class="material-icons" style="font-size: 15px">EG</i>
            {if $count}<span id="total_notif_number_wrapper" style=" top: -3px;right: -7px;">{$count}</span>{else}<span id="total_notif_number_wrapper" style=" top: -3px;right: -7px;">0</span>{/if}</a>
        <ul class="dropdown-menu dropdown-menu-right notifs_dropdown" style="min-width: 0px">
            <li><a href="{$hrefOrderHandle}" target="_blank">Order Handle</a></li>
            <li><a href="{$hrefFetchOrders}" target="_blank">Fetch Orders</a></li>
            <li><a href="{$hrefMarketplace}" target="_blank">MarcetPlace Elkjopnordic</a></li>
        </ul>
    </li>
</ul>
