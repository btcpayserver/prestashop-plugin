<h3 class="mb-1">{l s="Follow your order's status step-by-step" d='Shop.Theme.Customeraccount'}</h3>
{if $order_state == $os_paid}
  <p>{l s='Your order has been paid for.' d='Modules.Btcpay.Front'}</p>
  <p><strong>{l s="We will ship out your order as soon as we're able to." d='Modules.Btcpay.Front'}</strong></p>
  <p>{l s='If you have questions, comments or concerns, please contact our' d='Modules.Btcpay.Front'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team.' d='Modules.Btcpay.Front'}</a></p>
{elseif $order_state == $os_failed}
  <p class="warning">{l s='The invoice was not succesfully paid. Either because the invoice expired or your transaction never confirmed.' d='Modules.Btcpay.Front'}</p>
  <p>{l s='If you think this is an error, feel free to contact our' d='Modules.Btcpay.Front'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team.' d='Modules.Btcpay.Front'}</a></p>
{elseif $order_state == $os_waiting}
  <p>{l s="Your order is awaiting payment. If you haven't paid yet, you can <a href='%s' title='Go to the payment page' target='_blank'>click here to go back to the payment page</a>." sprintf=[$bitcoinPayment->getRedirect()] d='Modules.Btcpay.Front'|escape:'html'}</p>
  <p>{l s='If you have questions, comments or concerns, please contact our' d='Modules.Btcpay.Front'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team.' d='Modules.Btcpay.Front'}</a></p>
{elseif $order_state == $os_confirming}
  <p>{l s='Your order is awaiting confirmations.' d='Modules.Btcpay.Front'}</p>
  <p>{l s='Your order will be processed as soon as your payment is confirmed by the relevant crypto network.' d='Modules.Btcpay.Front'}</p>
  <p>{l s='If you have questions, comments or concerns, please contact our' d='Modules.Btcpay.Front'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team.' d='Modules.Btcpay.Front'}</a></p>
{/if}
<hr/>
<table class="table table-striped table-bordered table-labeled hidden-xs-down">
  <thead class="thead-default">
  <tr>
    <th>{l s='Date' d='Shop.Theme.Global'}</th>
    <th>{l s='Status' d='Shop.Theme.Global'}</th>
  </tr>
  </thead>
  <tbody>
  {foreach from=$presenter.history item=state}
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
