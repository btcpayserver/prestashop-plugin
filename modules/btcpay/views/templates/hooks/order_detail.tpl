<div class="payment-information my-2">
  <h4>{l s='Payment information' d='Modules.Btcpay.Global'}</h4>
  <div class="row">
    <div class="col-md-4 m-1 my-2">
      <p class="text-muted mb-0"><strong>Invoice</strong></p>
      <a class="configure-link" href="{$serverURL|escape:'htmlall':'UTF-8'}/i/{$invoice.id|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer nofollow">{$invoice.id|escape:'htmlall':'UTF-8'}</a>
    </div>
    <div class="col-md-4 m-1 my-2">
      <p class="text-muted mb-0"><strong>Status</strong></p>
        {if $invoice->isInvalid()}
          <span class="tag tag-danger tag-pill">{if $invoice->isMarked()}Marked invalid via BTCPay Server{else}Payment failed{/if}</span>
        {elseif $invoice->isSettled()}
          <span class="tag tag-success tag-pill">{if $invoice->isMarked()}Marked paid via BTCPay Server{else}Paid (and confirmed){/if}</span>
          {if $invoice->isOverPaid()}<span class="tag tag-info tag-pill">Overpaid</span>{/if}
        {elseif $invoice->isProcessing()}
          <span class="tag tag-primary tag-pill">Paid (pending confirmations)</span>
          {if $invoice->isOverPaid()}<span class="tag tag-info tag-pill">Overpaid</span>{/if}
        {elseif $invoice->isPartiallyPaid()}
          <span class="tag tag-warning tag-pill">Partially paid (awaiting more funds)</span>
        {elseif $invoice->isNew()}
          <span class="tag tag-info tag-pill">Awaiting payment</span>
        {elseif $invoice->isExpired()}
          <span class="tag tag-danger tag-pill">Invoice expired</span>
        {/if}
    </div>
  </div>

  {foreach $paymentMethods as $paymentMethod}
    {if not empty($paymentMethod->getPayments())}
      {assign currencyCode "_"|explode:$paymentMethod.paymentMethod|current}
      <h5 class="mt-2 mb-0">{$currencyCode|escape:'htmlall':'UTF-8'}</h5>

      <table id="{$currencyCode|escape:'htmlall':'UTF-8'}-details" class="table table-bordered my-2">
        <thead>
        <tr>
          <th class="table-head-rate">{l s='Rate' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-cart-amount">{l s='Invoice amount' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-paid-amount">{l s='Total amount paid in %s' sprintf=[$currencyCode|escape:'htmlall':'UTF-8'] d='Modules.Btcpay.Global'}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td>{$storeCurrency|escape:'htmlall':'UTF-8'} {$paymentMethod.rate|escape:'htmlall':'UTF-8'}</td>
          <td>{$paymentMethod.amount|escape:'htmlall':'UTF-8'} {$paymentMethod.paymentMethod|escape:'htmlall':'UTF-8'}</td>
          <td>{$paymentMethod.paymentMethodPaid|escape:'htmlall':'UTF-8'} {$paymentMethod.paymentMethod|escape:'htmlall':'UTF-8'}</td>
        </tr>
        </tbody>
      </table>

      <table id="{$currencyCode|escape:'htmlall':'UTF-8'}-payments" class="table table-bordered my-2">
        <thead>
        <tr>
          <th class="table-head-date">{l s='Date' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-amount">{l s='Amount' d='Modules.Btcpay.Global'}</th>
          <th class="table-head-destination">{l s='Transaction' d='Modules.Btcpay.Global'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $paymentMethod->getPayments() as $payment}
          <tr>
            <td>{$payment->getReceivedTimestamp()|date_format:"%Y-%m-%d %T"}</td>
            <td>{$payment.value|escape:'htmlall':'UTF-8'} {$currencyCode|escape:'htmlall':'UTF-8'}</td>
              {if $currencyCode == 'BTC'}
                <td><a href="https://mempool.space/tx/{$payment->getTransactionId()|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer nofollow">{$payment->getTransactionId()|escape:'htmlall':'UTF-8'}</a></td>
              {else}
                <td><a href="https://blockchair.com/search?q={$payment->getTransactionId()|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer nofollow">{$payment->getTransactionId()|escape:'htmlall':'UTF-8'}</a></td>
              {/if}
          </tr>
        {/foreach}
        </tbody>
      </table>
    {/if}
  {/foreach}
</div>

<div class="clearfix"></div>
