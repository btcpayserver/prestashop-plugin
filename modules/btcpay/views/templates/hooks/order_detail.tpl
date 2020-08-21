<div class="box">
  <div class="col-lg-6 col-md-6 col-sm-6">
    <h4>{l s='Payment Information' d='Modules.Btcpay.Global'}</h4>
    <dl>
      <dt>{l s='Invoice ID' d='Modules.Btcpay.Global'}</dt>
      <dd>
        <a href="{$server_url}/invoice?id={$payment_details.invoice_id}" target="_blank">
          {$payment_details.invoice_id}
        </a>
      </dd>
      <dt>{l s='Bitcoin address' d='Modules.Btcpay.Global'}</dt>
      <dd>{$payment_details.btc_address}</dd>
      <dt>{l s='Rate' d='Modules.Btcpay.Global'}</dt>
      <dd><span class="tag tag-info">{$currency_sign} {$payment_details.rate}</span></dd>
    </dl>
  </div>

  <div class="col-lg-6 col-md-6 col-sm-6">
    <h4>{l s='Order Information' d='Modules.Btcpay.Global'}</h4>
    <dl>
      <dt>{l s='Cart value' d='Modules.Btcpay.Global'}</dt>
      <dd><span class="tag tag-info">{$currency_sign} {$payment_details.amount}</span></dd>
      <dt>{l s='Cart value in Bitcoin' d='Modules.Btcpay.Global'}</dt>
      <dd><span class="tag tag-info">{$payment_details.btc_price} BTC</span></dd>
      {if $payment_details.btc_paid > 0}
        <dt>{l s='Amount paid' d='Modules.Btcpay.Global'}</dt>
        <dd>
          {if $payment_details.btc_paid < $payment_details.btc_price}
            <span class="tag tag-warning">{$payment_details.btc_paid} BTC</span>
          {else}
            <span class="tag tag-success">{$payment_details.btc_paid} BTC</span>
          {/if}
        </dd>
      {/if}
    </dl>
  </div>
  <div class="clearfix"></div>
</div>
