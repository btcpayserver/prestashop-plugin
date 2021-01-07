<div class="card mt-2" id="view_order_payments_block">
  <div class="card-header">
    <h3 class="card-header-title">{l s='BTCPay information' d='Modules.Btcpay.Global'}</h3>
  </div>

  <div class="card-body">
      <table class="table">
        <thead>
        <tr>
          <th class="table-head-transaction">{l s='Invoice ID' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-address">{l s='Bitcoin address' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-rate">{l s='Rate' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-cart-amount">{l s='Cart value' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-amount">{l s='Cart value in Bitcoin' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-paid-amount">{l s='Amount paid' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-paid">{l s='Paid' d='Modules.Btcpay.Global'}</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
          <tr>
            <td><a href="{$server_url}/invoices/{$payment_details.invoice_id}" target="_blank">{$payment_details.invoice_id}</a></td>
            <td><a href="https://www.blockstream.info/address/{$payment_details.btc_address}" target="_blank">{$payment_details.btc_address}</a></td>
            <td>{$currency_sign} {$payment_details.rate}</td>
            <td>{$currency_sign} {$payment_details.amount}</td>
            <td>{$payment_details.btc_price} BTC</td>
            <td>{$payment_details.btc_paid} BTC</td>
            {if $payment_details.btc_paid == $payment_details.btc_price}
            <td class="badge badge-success"><i class="material-icons" aria-hidden="true">check</i></td>
            {elseif $payment_details.btc_paid > $payment_details.btc_price}
            <td class="badge badge-warning"><i class="material-icons" aria-hidden="true">check</i></td>
            {else }
            <td class="badge badge-danger"><i class="material-icons" aria-hidden="true">clear</i></td>
            {/if}
          </tr>
        </tbody>
      </table>
  </div>
</div>
