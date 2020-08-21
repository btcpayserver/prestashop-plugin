<div class="panel">
  <div class="panel-heading"><i class="icon-bitcoin"></i> {l s='BTCPay' d='Modules.Btcpay.Admin'}</div>
  <div class="row">
    <div class="col-xs-6">
      <fieldset>
        <legend>{l s='Payment Information' d='Modules.Btcpay.Global'}</legend>
        <dl class="well list-detail">
          <dt>{l s='Invoice ID' d='Modules.Btcpay.Global'}</dt>
          <dd>
            <a href="{$server_url}/invoices/{$payment_details.invoice_id}" target="_blank">
              {$payment_details.invoice_id}
            </a>
          </dd>
          <dt>{l s='Bitcoin address' d='Modules.Btcpay.Global'}</dt>
          <dd>{$payment_details.btc_address}</dd>
          <dt>{l s='Rate' d='Modules.Btcpay.Global'}</dt>
          <dd>{$currency_sign} {$payment_details.rate}</dd>
        </dl>
      </fieldset>
    </div>

    <div class="col-xs-6">
      <fieldset>
        <legend>{l s='Order Information' d='Modules.Btcpay.Global'}</legend>
        <dl class="well list-detail">
          <dt>{l s='Cart value' d='Modules.Btcpay.Global'}</dt>
          <dd>{$currency_sign} {$payment_details.amount}</dd>
          <dt>{l s='Cart value in Bitcoin' d='Modules.Btcpay.Global'}</dt>
          <dd>{$payment_details.btc_price} BTC</dd>
          {if $payment_details.btc_paid > 0}
            <dt>{l s='Amount paid' d='Modules.Btcpay.Global'}</dt>
            <dd>
              {if $payment_details.btc_paid == $payment_details.btc_price}
                <span class="badge badge-success">{$payment_details.btc_paid} BTC</span>
              {elseif $payment_details.btc_paid > $payment_details.btc_price}
                <span class="badge badge-warning">{$payment_details.btc_paid} BTC</span>
              {else }
                <span class="badge badge-danger">{$payment_details.btc_paid} BTC</span>
              {/if}
            </dd>
          {/if}
        </dl>
      </fieldset>
    </div>
  </div>
</div>
