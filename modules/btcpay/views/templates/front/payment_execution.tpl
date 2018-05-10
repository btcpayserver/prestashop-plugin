{capture name=path}{l s='Bictoin payment.' mod='btcpay'}{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='btcpay'}</h2>

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='btcpay'}</p>
{else}

<h3>{l s='Bitcoin payment.' mod='btcpay'}</h3>

<form action="{$link->getModuleLink('btcpay', 'validation', [], true)|escape:'html'}" method="post">
<p>
	<img src="{$this_path}btcpay-plugin.png" alt="{l s='Pay with BitPay' mod='btcpay'}" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='You have chosen to pay by Bitcoin.' mod='btcpay'}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='btcpay'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='btcpay'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='btcpay'}
    {/if}
</p>
<p>
	{l s='Your Bitcoin invoice will be displayed on the next page.' mod='btcpay'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order."' mod='btcpay'}.</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='btcpay'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Bitcoin Payments' mod='btcpay'}</a>
</p>
</form>
{/if}

