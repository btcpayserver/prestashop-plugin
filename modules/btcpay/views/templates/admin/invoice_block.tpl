<div class="card mt-2" id="view_order_payments_block">
  <div class="card-header">
    <h3 class="card-header-title">{l s='BTCPay Server - Information' d='Modules.Btcpay.Global'}</h3>
  </div>

  <div class="card-body">
    <div class="col-md-12 mt-2 mb-4">
      <div class="info-block">
        <div class="row">
          <div class="col-sm text-center">
            <p class="text-muted mb-0"><strong>Invoice</strong></p>
            <a class="invoice-link font-size-100" href="{$server_url}/invoices/{$invoice.id}" target="_blank" rel="noopener noreferrer nofollow">{$invoice.id}</a>
          </div>

          <div class="col-sm text-center">
            <p class="text-muted mb-0"><strong>Status</strong></p>
            <p class="mb-0">
              {if $invoice->isInvalid()}
                <span class="badge badge-danger rounded font-size-100">{if $invoice->isMarked()}Marked invalid via BTCPay Server{else}Payment failed{/if}</span>
              {elseif $invoice->isSettled()}
                <span class="badge badge-success rounded font-size-100">{if $invoice->isMarked()}Marked paid via BTCPay Server{else}Paid (and confirmed){/if}</span>
                {if $invoice->isOverPaid()}<span class="badge badge-info rounded font-size-100">Overpaid</span>{/if}
              {elseif $invoice->isProcessing()}
                <span class="badge badge-primary rounded font-size-100">Paid (pending confirmations)</span>
                {if $invoice->isOverPaid()}<span class="badge badge-info rounded font-size-100">Overpaid</span>{/if}
              {elseif $invoice->isPartiallyPaid()}
                <span class="badge badge-warning rounded font-size-100">Partially paid (awaiting more funds)</span>
              {elseif $invoice->isNew()}
                <span class="badge badge-info rounded font-size-100">Awaiting payment</span>
              {elseif $invoice->isExpired()}
                <span class="badge badge-danger rounded font-size-100">Invoice expired</span>
              {/if}
            </p>
          </div>
        </div>
      </div>
    </div>

    {if $invoice->isInvalid() or $invoice->isExpired() or $invoice->isOverPaid()}
      <div class="col-md-12 mt-2 mb-4">
        <div class="alert alert-warning mb-0" role="alert">
          <p class="alert-text"><strong>Warning</strong>: Make sure to double-check the invoice before shipping it</p>
        </div>
      </div>
    {/if}

    {if $paymentReceived}
      <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
        {foreach $paymentMethods as $paymentMethod}
          {if not empty($paymentMethod->getPayments())}
            {assign currencyCode "_"|explode:$paymentMethod.paymentMethod|current}
            <a class="nav-item nav-link{if $paymentMethod@first} active{/if}" id="nav-{$currencyCode|strtolower}-tab" data-toggle="tab" href="#nav-{$currencyCode|strtolower}" role="tab" aria-controls="nav-{$currencyCode|strtolower}" aria-selected="true">
              <strong>{$currencyCode}</strong>
            </a>
          {/if}
        {/foreach}
        </div>
      </nav>
      <div class="tab-content" id="nav-tabContent">
      {foreach $paymentMethods as $paymentMethod}
        {if not empty($paymentMethod->getPayments())}
              {assign currencyCode "_"|explode:$paymentMethod.paymentMethod|current}
              <div class="tab-pane fade{if $paymentMethod@first} show active{/if}" id="nav-{$currencyCode|strtolower}" role="tabpanel" aria-labelledby="nav-{$currencyCode|strtolower}-tab">
                <table id="{$currencyCode}-details" class="table table-bordered my-2">
                  <thead>
                  <tr>
                    <th class="table-head-rate">{l s='Rate' d='Modules.Btcpay.Global'}</th>
                    <th class="table-head-cart-amount">{l s='Invoice amount' d='Modules.Btcpay.Global'}</th>
                    <th class="table-head-paid-amount">{l s='Total amount paid in %s' sprintf=[$currencyCode] d='Modules.Btcpay.Global'}</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>{$storeCurrency} {$paymentMethod.rate}</td>
                    <td>{$paymentMethod.amount} {$paymentMethod.paymentMethod}</td>
                    <td>{$paymentMethod.paymentMethodPaid} {$paymentMethod.paymentMethod}</td>
                  </tr>
                  </tbody>
                </table>
                <table id="{$currencyCode}-payments" class="table table-bordered my-2">
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
                      <td>{$payment.value} {$currencyCode}</td>
                        {if $currencyCode == 'BTC'}
                          <td><a href="https://mempool.space/tx/{$payment->getTransactionId()}" target="_blank" rel="noopener noreferrer nofollow">{$payment->getTransactionId()}</a></td>
                        {else}
                          <td><a href="https://blockchair.com/search?q={$payment->getTransactionId()}" target="_blank" rel="noopener noreferrer nofollow">{$payment->getTransactionId()}</a></td>
                        {/if}
                    </tr>
                  {/foreach}
                  </tbody>
                </table>
              </div>
            {/if}
      {/foreach}
      </div>
    {/if}
  </div>
</div>
