{if $order.current_state == '42'}
    <p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='btcpay'}
        <br/><br/>
        <strong>{l s='Your order will be sent as soon as your payment is confirmed by the Bitcoin network.' mod='btcpay'}</strong>
        <br/><br/>{l s='If you have questions, comments or concerns, please contact our' mod='btcpay'} <a
                href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='btcpay'}</a>
    </p>
{elseif $order.current_state == '41'}
    <p class="warning">
        {l s="We noticed a problem with your order. If you think this is an error, feel free to contact our" mod='btcpay'}
        <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='btcpay'}</a>.
    </p>
{elseif $order.current_state == '40' or $order.current_state == '39'}
    <p>{l s='Your order on %s is awaiting Bitcoin confirmations.' sprintf=$shop_name mod='btcpay'}
        <br/><br/>
        <strong>{l s='Your order will be sent as soon as your payment is confirmed by the Bitcoin network.' mod='btcpay'}</strong>
        <br/><br/>{l s='If you have questions, comments or concerns, please contact our' mod='btcpay'} <a
                href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='btcpay'}</a>
    </p>
{else}
    <h3>{l s='Follow your order\'s status step-by-step' d='Shop.Theme.Customeraccount'}</h3>
    <table class="table table-striped table-bordered table-labeled hidden-xs-down">
        <thead class="thead-default">
        <tr>
            <th>{l s='Date' d='Shop.Theme.Global'}</th>
            <th>{l s='Status' d='Shop.Theme.Global'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$order.history item=state}
            <tr>
                <td>{$state.history_date}</td>
                <td>
                <span class="label label-pill {$state.contrast}" style="background-color:{$state.color}">
                  {$state.ostate_name}
                </span>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{/if}
