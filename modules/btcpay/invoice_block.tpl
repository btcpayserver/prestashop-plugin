<div class="panel kpi-container">
    <fieldset>
        <legend>{l s='BtcPay Information' mod='btcpay'}</legend>
        <div id="info">
            <dl class="well list-detail">
                <dt>{l s='Bitcoin address' mod='btcpay'}</dt>
                <dd>{$payment_details.btc_address|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Status' mod='btcpay'}</dt>
                <dd>{$payment_details.status|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Cart value' mod='btcpay'}</dt>
                <dd>{$currency_sign}{$payment_details.amount}</dd>
                <dt>{l s='Rate' mod='btcpay'}</dt>
                <dd>${$payment_details.rate}</dd>
                <dt>{l s='Cart value in Bitcoin' mod='btcpay'}</dt>
                <dd>{$payment_details.btc_price} BTC</dd>
                {if $payment_details.btc_paid > 0}
                    <dt>{l s='Amount paid' mod='btcpay'}</dt>
                    <dd><span class="badge badge-success">{$payment_details.btc_paid} BTC</span></dd>
                    {if $payment_details.btc_paid != $payment_details.btc_price}
                        <dt>{l s='Payment issue' mod='btcpay'}</dt>
                        <dd>
                            <span class="badge badge-warning">{l s='Amount paid not matching cart value' mod='btcpay'}</span>
                        </dd>
                    {/if}
                {/if}
            </dl>
        </div>
    </fieldset>
</div>
