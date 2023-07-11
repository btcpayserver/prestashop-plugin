<?php

const _DB_PREFIX_ = 'ps_';

abstract class AbstractAssetManager extends AbstractAssetManagerCore
{
}
abstract class AbstractCheckoutStep extends AbstractCheckoutStepCore
{
}
abstract class AbstractForm extends AbstractFormCore
{
}
abstract class AbstractLogger extends AbstractLoggerCore
{
}
abstract class AdminStatsTabController extends AdminStatsTabControllerCore
{
}
abstract class Cache extends CacheCore
{
}
abstract class CarrierModule extends CarrierModuleCore
{
}
abstract class Controller extends ControllerCore
{
}
abstract class Db extends DbCore
{
}
abstract class HTMLTemplate extends HTMLTemplateCore
{
}
abstract class Module extends ModuleCore
{
}
abstract class ModuleAdminController extends ModuleAdminControllerCore
{
}
abstract class ModuleGraph extends ModuleGraphCore
{
}
abstract class ModuleGraphEngine extends ModuleGraphEngineCore
{
}
abstract class ModuleGrid extends ModuleGridCore
{
}
abstract class ModuleGridEngine extends ModuleGridEngineCore
{
}
abstract class ObjectModel extends ObjectModelCore
{
}
abstract class PaymentModule extends PaymentModuleCore
{
	protected $is_eu_compatible;
}
abstract class ProductListingFrontController extends ProductListingFrontControllerCore
{
}
abstract class ProductPresentingFrontController extends ProductPresentingFrontControllerCore
{
}
abstract class StockManagerModule extends StockManagerModuleCore
{
}
abstract class TaxManagerModule extends TaxManagerModuleCore
{
}
abstract class TreeToolbarButton extends TreeToolbarButtonCore
{
}
class Access extends AccessCore
{
}
class Address extends AddressCore
{
}
class AddressChecksum extends AddressChecksumCore
{
}
class AddressController extends AddressControllerCore
{
}
class AddressesController extends AddressesControllerCore
{
}
class AddressFormat extends AddressFormatCore
{
}
class AdminAccessController extends AdminAccessControllerCore
{
}
class AdminAttributesGroupsController extends AdminAttributesGroupsControllerCore
{
}
class AdminCarriersController extends AdminCarriersControllerCore
{
}
class AdminCarrierWizardController extends AdminCarrierWizardControllerCore
{
}
class AdminCartRulesController extends AdminCartRulesControllerCore
{
}
class AdminCartsController extends AdminCartsControllerCore
{
}
class AdminController extends AdminControllerCore
{
}
class AdminCountriesController extends AdminCountriesControllerCore
{
}
class AdminCustomerThreadsController extends AdminCustomerThreadsControllerCore
{
}
class AdminDashboardController extends AdminDashboardControllerCore
{
}
class AdminFeaturesController extends AdminFeaturesControllerCore
{
}
class AdminGendersController extends AdminGendersControllerCore
{
}
class AdminGroupsController extends AdminGroupsControllerCore
{
}
class AdminImagesController extends AdminImagesControllerCore
{
}
class AdminImportController extends AdminImportControllerCore
{
}
class AdminLegacyLayoutController extends AdminLegacyLayoutControllerCore
{
}
class AdminLoginController extends AdminLoginControllerCore
{
}
class AdminModulesController extends AdminModulesControllerCore
{
}
class AdminModulesPositionsController extends AdminModulesPositionsControllerCore
{
}
class AdminNotFoundController extends AdminNotFoundControllerCore
{
}
class AdminPdfController extends AdminPdfControllerCore
{
}
class AdminProductsController extends AdminProductsControllerCore
{
}
class AdminQuickAccessesController extends AdminQuickAccessesControllerCore
{
}
class AdminRequestSqlController extends AdminRequestSqlControllerCore
{
}
class AdminReturnController extends AdminReturnControllerCore
{
}
class AdminSearchConfController extends AdminSearchConfControllerCore
{
}
class AdminSearchController extends AdminSearchControllerCore
{
}
class AdminShopController extends AdminShopControllerCore
{
}
class AdminShopGroupController extends AdminShopGroupControllerCore
{
}
class AdminShopUrlController extends AdminShopUrlControllerCore
{
}
class AdminSpecificPriceRuleController extends AdminSpecificPriceRuleControllerCore
{
}
class AdminStatesController extends AdminStatesControllerCore
{
}
class AdminStatsController extends AdminStatsControllerCore
{
}
class AdminStatusesController extends AdminStatusesControllerCore
{
}
class AdminStoresController extends AdminStoresControllerCore
{
}
class AdminSuppliersController extends AdminSuppliersControllerCore
{
}
class AdminTabsController extends AdminTabsControllerCore
{
}
class AdminTagsController extends AdminTagsControllerCore
{
}
class AdminTaxRulesGroupController extends AdminTaxRulesGroupControllerCore
{
}
class AdminTranslationsController extends AdminTranslationsControllerCore
{
}
class Alias extends AliasCore
{
}
class Attachment extends AttachmentCore
{
}
class AttachmentController extends AttachmentControllerCore
{
}
class AttributeGroup extends AttributeGroupCore
{
}
class AuthController extends AuthControllerCore
{
}
class CacheApc extends CacheApcCore
{
}
class CacheMemcache extends CacheMemcacheCore
{
}
class CacheMemcached extends CacheMemcachedCore
{
}
class CacheXcache extends CacheXcacheCore
{
}
class Carrier extends CarrierCore
{
}
class Cart extends CartCore
{
}
class CartChecksum extends CartChecksumCore
{
}
class CartController extends CartControllerCore
{
}
class CartRule extends CartRuleCore
{
}
class Category extends CategoryCore
{
}
class CccReducer extends CccReducerCore
{
}
class ChangeCurrencyController extends ChangeCurrencyControllerCore
{
}
class Chart extends ChartCore
{
}
class CheckoutAddressesStep extends CheckoutAddressesStepCore
{
}
class CheckoutDeliveryStep extends CheckoutDeliveryStepCore
{
}
class CheckoutPaymentStep extends CheckoutPaymentStepCore
{
}
class CheckoutPersonalInformationStep extends CheckoutPersonalInformationStepCore
{
}
class CheckoutProcess extends CheckoutProcessCore
{
}
class CheckoutSession extends CheckoutSessionCore
{
}
class CMS extends CMSCore
{
}
class CMSCategory extends CMSCategoryCore
{
}
class CmsController extends CmsControllerCore
{
}
class CMSRole extends CMSRoleCore
{
}
class Combination extends CombinationCore
{
}
class ConditionsToApproveFinder extends ConditionsToApproveFinderCore
{
}
class Configuration extends ConfigurationCore
{
}
class ConfigurationKPI extends ConfigurationKPICore
{
}
class ConfigurationTest extends ConfigurationTestCore
{
}
class Connection extends ConnectionCore
{
}
class ConnectionsSource extends ConnectionsSourceCore
{
}
class Contact extends ContactCore
{
}
class ContactController extends ContactControllerCore
{
}
class Context extends ContextCore
{
}
class Cookie extends CookieCore
{
}
class Country extends CountryCore
{
}
class CssMinifier extends CssMinifierCore
{
}
class CSV extends CSVCore
{
}
class Currency extends CurrencyCore
{
}
class Customer extends CustomerCore
{
}
class CustomerAddressForm extends CustomerAddressFormCore
{
}
class CustomerAddressFormatter extends CustomerAddressFormatterCore
{
}
class CustomerAddressPersister extends CustomerAddressPersisterCore
{
}
class CustomerForm extends CustomerFormCore
{
}
class CustomerFormatter extends CustomerFormatterCore
{
}
class CustomerLoginForm extends CustomerLoginFormCore
{
}
class CustomerLoginFormatter extends CustomerLoginFormatterCore
{
}
class CustomerMessage extends CustomerMessageCore
{
}
class CustomerPersister extends CustomerPersisterCore
{
}
class CustomerThread extends CustomerThreadCore
{
}
class Customization extends CustomizationCore
{
}
class CustomizationField extends CustomizationFieldCore
{
}
class DateRange extends DateRangeCore
{
}
class DbMySQLi extends DbMySQLiCore
{
}
class DbPDO extends DbPDOCore
{
}
class DbQuery extends DbQueryCore
{
}
class Delivery extends DeliveryCore
{
}
class DeliveryOptionsFinder extends DeliveryOptionsFinderCore
{
}
class DiscountController extends DiscountControllerCore
{
}
class Dispatcher extends DispatcherCore
{
}
class Employee extends EmployeeCore
{
}
class Feature extends FeatureCore
{
}
class FeatureValue extends FeatureValueCore
{
}
class FileLogger extends FileLoggerCore
{
}
class FileUploader extends FileUploaderCore
{
}
class FormField extends FormFieldCore
{
}
class FrontController extends FrontControllerCore
{
}
class Gender extends GenderCore
{
}
class GetFileController extends GetFileControllerCore
{
}
class Group extends GroupCore
{
}
class GroupReduction extends GroupReductionCore
{
}
class Guest extends GuestCore
{
}
class GuestTrackingController extends GuestTrackingControllerCore
{
}
class Helper extends HelperCore
{
}
class HelperCalendar extends HelperCalendarCore
{
}
class HelperForm extends HelperFormCore
{
}
class HelperImageUploader extends HelperImageUploaderCore
{
}
class HelperKpi extends HelperKpiCore
{
}
class HelperKpiRow extends HelperKpiRowCore
{
}
class HelperList extends HelperListCore
{
}
class HelperOptions extends HelperOptionsCore
{
}
class HelperShop extends HelperShopCore
{
}
class HelperTreeCategories extends HelperTreeCategoriesCore
{
}
class HelperTreeShops extends HelperTreeShopsCore
{
}
class HelperUploader extends HelperUploaderCore
{
}
class HelperView extends HelperViewCore
{
}
class HistoryController extends HistoryControllerCore
{
}
class Hook extends HookCore
{
}
class HTMLTemplateDeliverySlip extends HTMLTemplateDeliverySlipCore
{
}
class HTMLTemplateInvoice extends HTMLTemplateInvoiceCore
{
}
class HTMLTemplateOrderReturn extends HTMLTemplateOrderReturnCore
{
}
class HTMLTemplateOrderSlip extends HTMLTemplateOrderSlipCore
{
}
class HTMLTemplateSupplyOrderForm extends HTMLTemplateSupplyOrderFormCore
{
}
class IdentityController extends IdentityControllerCore
{
}
class Image extends ImageCore
{
}
class ImageManager extends ImageManagerCore
{
}
class ImageType extends ImageTypeCore
{
}
class IndexController extends IndexControllerCore
{
}
class JavascriptManager extends JavascriptManagerCore
{
}
class JsMinifier extends JsMinifierCore
{
}
class Language extends LanguageCore
{
}
class Link extends LinkCore
{
}
class LinkProxy extends LinkProxyCore
{
}
class LocalizationPack extends LocalizationPackCore
{
}
class Mail extends MailCore
{
}
class Manufacturer extends ManufacturerCore
{
}
class Media extends MediaCore
{
}
class Message extends MessageCore
{
}
class Meta extends MetaCore
{
}
class ModuleFrontController extends ModuleFrontControllerCore
{
}
class MyAccountController extends MyAccountControllerCore
{
}
class Notification extends NotificationCore
{
}
class Order extends OrderCore
{
}
class OrderCarrier extends OrderCarrierCore
{
}
class OrderCartRule extends OrderCartRuleCore
{
}
class OrderConfirmationController extends OrderConfirmationControllerCore
{
}
class OrderController extends OrderControllerCore
{
}
class OrderDetail extends OrderDetailCore
{
}
class OrderDetailController extends OrderDetailControllerCore
{
}
class OrderFollowController extends OrderFollowControllerCore
{
}
class OrderHistory extends OrderHistoryCore
{
}
class OrderInvoice extends OrderInvoiceCore
{
}
class OrderMessage extends OrderMessageCore
{
}
class OrderPayment extends OrderPaymentCore
{
}
class OrderReturn extends OrderReturnCore
{
}
class OrderReturnController extends OrderReturnControllerCore
{
}
class OrderReturnState extends OrderReturnStateCore
{
}
class OrderSlip extends OrderSlipCore
{
}
class OrderSlipController extends OrderSlipControllerCore
{
}
class OrderState extends OrderStateCore
{
}
class Pack extends PackCore
{
}
class Page extends PageCore
{
}
class PageNotFoundController extends PageNotFoundControllerCore
{
}
class PasswordController extends PasswordControllerCore
{
}
class PaymentOptionsFinder extends PaymentOptionsFinderCore
{
}
class PDF extends PDFCore
{
}
class PDFGenerator extends PDFGeneratorCore
{
}
class PdfInvoiceController extends PdfInvoiceControllerCore
{
}
class PdfOrderReturnController extends PdfOrderReturnControllerCore
{
}
class PdfOrderSlipController extends PdfOrderSlipControllerCore
{
}
class PhpEncryption extends PhpEncryptionCore
{
}
class PhpEncryptionEngine extends PhpEncryptionEngineCore
{
}
class PrestaShopBackup extends PrestaShopBackupCore
{
}
class PrestaShopCollection extends PrestaShopCollectionCore
{
}
class PrestaShopDatabaseException extends PrestaShopDatabaseExceptionCore
{
}
class PrestaShopException extends PrestaShopExceptionCore
{
}
class PrestaShopLogger extends PrestaShopLoggerCore
{
}
class PrestaShopModuleException extends PrestaShopModuleExceptionCore
{
}
class PrestaShopPaymentException extends PrestaShopPaymentExceptionCore
{
}
class Product extends ProductCore
{
}
class ProductAssembler extends ProductAssemblerCore
{
}
class ProductController extends ProductControllerCore
{
}
class ProductDownload extends ProductDownloadCore
{
}
class ProductPresenterFactory extends ProductPresenterFactoryCore
{
}
class ProductSale extends ProductSaleCore
{
}
class ProductSupplier extends ProductSupplierCore
{
}
class Profile extends ProfileCore
{
}
class QuickAccess extends QuickAccessCore
{
}
class RangePrice extends RangePriceCore
{
}
class RangeWeight extends RangeWeightCore
{
}
class RequestSql extends RequestSqlCore
{
}
class Risk extends RiskCore
{
}
class Search extends SearchCore
{
}
class SearchEngine extends SearchEngineCore
{
}
class Shop extends ShopCore
{
}
class ShopGroup extends ShopGroupCore
{
}
class ShopUrl extends ShopUrlCore
{
}
class SitemapController extends SitemapControllerCore
{
}
class SmartyCustom extends SmartyCustomCore
{
}
class SmartyResourceModule extends SmartyResourceModuleCore
{
}
class SmartyResourceParent extends SmartyResourceParentCore
{
}
class SpecificPrice extends SpecificPriceCore
{
}
class SpecificPriceRule extends SpecificPriceRuleCore
{
}
class State extends StateCore
{
}
class StatisticsController extends StatisticsControllerCore
{
}
class Stock extends StockCore
{
}
class StockAvailable extends StockAvailableCore
{
}
class StockManager extends StockManagerCore
{
}
class StockManagerFactory extends StockManagerFactoryCore
{
}
class StockMvt extends StockMvtCore
{
}
class StockMvtReason extends StockMvtReasonCore
{
}
class StockMvtWS extends StockMvtWSCore
{
}
class Store extends StoreCore
{
}
class StoresController extends StoresControllerCore
{
}
class StylesheetManager extends StylesheetManagerCore
{
}
class Supplier extends SupplierCore
{
}
class SupplyOrder extends SupplyOrderCore
{
}
class SupplyOrderDetail extends SupplyOrderDetailCore
{
}
class SupplyOrderHistory extends SupplyOrderHistoryCore
{
}
class SupplyOrderReceiptHistory extends SupplyOrderReceiptHistoryCore
{
}
class SupplyOrderState extends SupplyOrderStateCore
{
}
class Tab extends TabCore
{
}
class Tag extends TagCore
{
}
class Tax extends TaxCore
{
}
class TaxCalculator extends TaxCalculatorCore
{
}
class TaxConfiguration extends TaxConfigurationCore
{
}
class TaxManagerFactory extends TaxManagerFactoryCore
{
}
class TaxRule extends TaxRuleCore
{
}
class TaxRulesGroup extends TaxRulesGroupCore
{
}
class TaxRulesTaxManager extends TaxRulesTaxManagerCore
{
}
class TemplateFinder extends TemplateFinderCore
{
}
class Tools extends ToolsCore
{
}
class Translate extends TranslateCore
{
}
class TranslatedConfiguration extends TranslatedConfigurationCore
{
}
class Tree extends TreeCore
{
}
class TreeToolbar extends TreeToolbarCore
{
}
class TreeToolbarLink extends TreeToolbarLinkCore
{
}
class TreeToolbarSearch extends TreeToolbarSearchCore
{
}
class TreeToolbarSearchCategories extends TreeToolbarSearchCategoriesCore
{
}
class Upgrader extends UpgraderCore
{
}
class Uploader extends UploaderCore
{
}
class Validate extends ValidateCore
{
}
class ValidateConstraintTranslator extends ValidateConstraintTranslatorCore
{
}
class Warehouse extends WarehouseCore
{
}
class WarehouseProductLocation extends WarehouseProductLocationCore
{
}
class WebserviceException extends WebserviceExceptionCore
{
}
class WebserviceKey extends WebserviceKeyCore
{
}
class WebserviceOutputBuilder extends WebserviceOutputBuilderCore
{
}
class WebserviceOutputJSON extends WebserviceOutputJSONCore
{
}
class WebserviceOutputXML extends WebserviceOutputXMLCore
{
}
class WebserviceRequest extends WebserviceRequestCore
{
}
class WebserviceSpecificManagementImages extends WebserviceSpecificManagementImagesCore
{
}
class WebserviceSpecificManagementSearch extends WebserviceSpecificManagementSearchCore
{
}
class Zone extends ZoneCore
{
}

/*              Class aliases              */
class Autoload extends PrestaShopAutoload
{
}
class Backup extends PrestaShopBackup
{
}
class Collection extends PrestaShopCollection
{
}
class Logger extends PrestaShopLogger
{
}
