<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QtapAdminsController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\QtapAffiliateController;
use App\Http\Controllers\CampaignsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\TicketSupportController;
use App\Http\Controllers\WeServController;
use App\Http\Controllers\QtapClientsController;
use App\Http\Controllers\CustomerInfoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientPricingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TablesController;
use App\Http\Controllers\MealsCategoriesController;
use App\Http\Controllers\MealsController;
use App\Http\Controllers\MealsDiscountController;
use App\Http\Controllers\MealsExtraController;
use App\Http\Controllers\MealsSizeController;
use App\Http\Controllers\MealsSpecialOffersController;
use App\Http\Controllers\MealsVariantsController;
use App\Http\Controllers\RestaurantUsersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RestaurantStaffController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DeliveryRiderController;
use App\Http\Controllers\DeliveryAreaController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\RestaurantUserStaffController;
use App\Http\Controllers\OrdersProcessingController;
use App\Http\Controllers\FeedbackRestaurantController;
use App\Http\Controllers\FaqQtapController;
use App\Http\Controllers\ClientsTransactionsController;
use App\Http\Controllers\GovernorateController;
use App\Models\qtap_admins;
use App\Http\Controllers\CoupPlanController;
use App\Http\Controllers\ChatSupportController;
use App\Http\Controllers\LanguagePreferenceController;

use App\Http\Middleware\CheckClient;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//-----------------------------------------AUTH------------------------
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->middleware('check.user.active');
Route::post('/checkCookies', [AuthController::class, 'checkAuth']);
Route::post('/resendOTP', [AuthController::class, 'resendOTP']);

Route::post('/send_phone_otp', [AuthController::class, 'send_phone_otp']);
Route::post('/verify_phone_otp', [AuthController::class, 'verify_phone_otp']);
Route::post('/resend_phone_otp', [AuthController::class, 'resend_phone_otp']);




Route::post('/chat_support', [ChatSupportController::class, 'store']);
Route::post('/chat_support/get_last', [ChatSupportController::class, 'get_last_user']);

Route::post('/chat_support/closed', [ChatSupportController::class, 'closeChatByEmail']);













//------------------------------------------WEBSITE---------------------------------------
Route::get('home', [homeController::class, 'index']);

Route::get('home_affiliate/{affiliate_code}', [homeController::class, 'home_affiliate']);


Route::middleware('auth:api')->get('/user', function (Request $request) {

    return $request->user();
});



//------------------------------------reset_pass_verfiy_email------------------------------------------------------------------
Route::post('sendOTP', [AuthController::class, 'sendOTP'])->name('sendOTP');
Route::post('receiveOTP', [AuthController::class, 'receiveOTP'])->name('receiveOTP');
Route::post('resetpassword', [AuthController::class, 'resetpassword'])->name('resetpassword');
Route::post('verfiy_email', [AuthController::class, 'verfiy_email'])->name('verfiy_email');

//-----------------------------------------------------------------------------------------





//-------------------------------------------ADMIN OR CLIENT-------------------------------

Route::middleware('admin_or_client')->group(function () {

    Route::post('clients_update_profile/{id}', [QtapClientsController::class, 'update_profile']);
    Route::post('clients_update_menu/{id}', [QtapClientsController::class, 'update_menu']);

    Route::get('feedback_client', [FeedbackController::class, 'index']);
    Route::put('feedback_client/{id}', [FeedbackController::class, 'update']);
    Route::delete('feedback_client/{id}', [FeedbackController::class, 'destroy']);

############BY_MAJD
    Route::get('client/subscribtion/get_all', [ClientPricingController::class, 'clientSubscriptions']);
    Route::get('client/subscribtion/show/{client_pricing_id}', [ClientPricingController::class, 'show_C']);
    Route::post('client/subscribtion/chang_request/{client_pricing_id}', [ClientPricingController::class, 'requestSubscriptionChange']);
    Route::get('client/subscribtion/chang_request/get_all_my_request', [ClientPricingController::class, 'getPendingChangeRequests']);
    Route::delete('client/subscribtion/chang_request/delete/{client_pricing_id}', [ClientPricingController::class, 'cancelPendingRequest']);

    Route::post('language-preference/store', [LanguagePreferenceController::class, 'storeLanguagePreference']);
    Route::get('/language-preference/get', [LanguagePreferenceController::class, 'getLanguagePreference']);

});

//-------------------------------------------ADMIN OR AFFILIATE-----------------------------

Route::middleware('admin_or_affiliate')->group(function () {


    Route::post('qtap_affiliate/{id}', [QtapAffiliateController::class, 'update']);
});

Route::get('pricing', [PricingController::class, 'index'])->name('pricing');





//---------------------------------------------ADMIN----------------------------------------

Route::middleware('auth:qtap_admins')->group(function () {

    #by_majd
    Route::get('admin/subscribtion/get_all', [ClientPricingController::class, 'index']);
    Route::post('admin/subscribtion/active/{client_pricing_id}', [ClientPricingController::class, 'activateSubscription']);
    Route::get('admin/subscribtion/show/{client_pricing_id}', [ClientPricingController::class, 'show']);
    Route::get('admin/subscribtion/change_request', [ClientPricingController::class, 'getPendingChangeRequestsAdmin']);
    Route::get('admin/subscribtion/change_request/show/{id}', [ClientPricingController::class, 'showPendingRequestAdmin']);
    Route::post('admin/subscribtion/update_change_request/{id}', [ClientPricingController::class, 'updateRequestStatus']);


    Route::get('affiliate_transactions_all', [QtapAffiliateController::class, 'affiliate_transactions_all'])->name('affiliate_transactions_all');


    Route::post('qtap_clients/{id}', [QtapClientsController::class, 'update']);

    // Route::get('discount', [DiscountController::class, 'index']);


    Route::get('get_client_info/{id}', [QtapClientsController::class, 'get_client_info']);
    Route::get('get_affiliate_info/{id}', [QtapAffiliateController::class, 'get_affiliate_info']);



    Route::prefix('coupons_plan')->group(function () {
        Route::get('index/', [CoupPlanController::class, 'index']); // عرض الكل
        Route::post('store/', [CoupPlanController::class, 'store']); // إنشاء جديد
        Route::get('show/{id}', [CoupPlanController::class, 'show']); // عرض واحد
        Route::put('update/{id}', [CoupPlanController::class, 'update']); // تعديل
        Route::delete('destroy/{id}', [CoupPlanController::class, 'destroy']); // حذف
    });



    Route::get('ticket_client', [TicketSupportController::class, 'index']);
    Route::put('ticket_client/{id}', [TicketSupportController::class, 'update']);


    //--------------------------------------------------------------------------------------------

    Route::post('active_clients/{id}', [QtapAdminsController::class, 'active_clients'])->name('active_clients');

    //-------------discount--------
    Route::post('discount', [DiscountController::class, 'store']);
    Route::put('discount/{id}', [DiscountController::class, 'update']);
    Route::delete('discount/{id}', [DiscountController::class, 'destroy']);



    Route::get('/chat_support/index', [ChatSupportController::class, 'index']);
    Route::get('/chat_support/show/{chat_id}', [ChatSupportController::class, 'show']);
    Route::post('/chat_support/replay', [ChatSupportController::class, 'store_replay']);





    //-------------chat--------
    Route::get('customer_info', [CustomerInfoController::class, 'index']);
    Route::put('customer_info/{id}', [CustomerInfoController::class, 'update']);
    Route::delete('customer_info/{id}', [CustomerInfoController::class, 'delete']);

    //-------------affiliate--------

    Route::get('affiliate', [QtapAffiliateController::class, 'index']);
    Route::delete('affiliate/{id}', [QtapAffiliateController::class, 'destroy']);


    //-------------clinet--------
    Route::delete('qtap_clients/{id}', [QtapClientsController::class, 'destroy']);



    //-------------website--------
    Route::get('we_serv', [WeServController::class, 'index']);
    Route::post('we_serv', [WeServController::class, 'store']);
    Route::post('we_serv/{id}', [WeServController::class, 'update']);
    Route::delete('we_serv/{id}', [WeServController::class, 'destroy']);


    // Route::resource('pricing', PricingController::class);

    // Route::get('pricing', [PricingController::class, 'pricing'])->name('pricing');
    Route::post('pricing', [PricingController::class, 'store'])->name('pricing');
    Route::put('pricing/{id}', [PricingController::class, 'update'])->name('pricing');
    Route::delete('pricing/{id}', [PricingController::class, 'destroy'])->name('pricing');



    //-------------dashboard--------
    Route::resource('note', NoteController::class);
    Route::resource('currency', CurrencyController::class);

    // Route::resource('products', ProductsController::class);

    Route::resource('campaigns', CampaignsController::class);

    Route::post('products', [ProductsController::class, 'store']);
    Route::post('products/{id}', [ProductsController::class, 'update']);
    Route::delete('products/{id}', [ProductsController::class, 'destroy']);


    Route::post('dashboard', [QtapAdminsController::class, 'dashboard'])->name('dashboard');
    Route::post('Sales/{id}', [QtapAdminsController::class, 'Sales'])->name('Sales');
    Route::post('Sales_by_days/{id}', [QtapAdminsController::class, 'Sales_by_days'])->name('Sales_by_days');
    Route::post('Performance/{startYear}-{endYear}', [QtapAdminsController::class, 'Performance'])->name('Performance');


    Route::post('wallet/{year}', [QtapAdminsController::class, 'wallet'])->name('wallet');
    Route::get('Deposits/{startDate}/{endDate}', [QtapAdminsController::class, 'Deposits'])->name('Deposits');



    Route::get('affiliate_Revenues/{year}', [QtapAffiliateController::class, 'affiliate_Revenues'])->name('affiliate_Revenues');






    Route::prefix('settings')->group(function () {
        Route::post('content', [SettingsController::class, 'createSettingContent']);
        Route::post('content/{id}', [SettingsController::class, 'updateSettingContent']);
        Route::get('content', [SettingsController::class, 'getSettingContent']);
        Route::delete('content/{id}', [SettingsController::class, 'deleteSettingContent']);



        Route::post('faq', [SettingsController::class, 'createSettingFaq']);
        Route::post('faq/{id}', [SettingsController::class, 'updateSettingFaq']);
        Route::get('faq', [SettingsController::class, 'getSettingFaq']);
        Route::delete('faq/{id}', [SettingsController::class, 'deleteSettingFaq']);

        Route::post('features', [SettingsController::class, 'createSettingFeatures']);
        Route::post('features/{id}', [SettingsController::class, 'updateSettingFeatures']);
        Route::get('features', [SettingsController::class, 'getSettingFeatures']);
        Route::delete('features/{id}', [SettingsController::class, 'deleteSettingFeatures']);

        Route::post('our-clients', [SettingsController::class, 'createSettingOurClients']);
        Route::post('our-clients/{id}', [SettingsController::class, 'updateSettingOurClients']);
        Route::get('our-clients', [SettingsController::class, 'getSettingOurClients']);
        Route::delete('our-clients/{id}', [SettingsController::class, 'deleteSettingOurClients']);

        Route::post('payment', [SettingsController::class, 'createSettingPayment']);
        Route::post('payment/{id}', [SettingsController::class, 'updateSettingPayment']);
        Route::get('payment', [SettingsController::class, 'getSettingPayment']);
        Route::delete('payment/{id}', [SettingsController::class, 'deleteSettingPayment']);

        Route::post('videos', [SettingsController::class, 'createSettingVideos']);
        Route::post('videos/{id}', [SettingsController::class, 'updateSettingVideos']);
        Route::get('videos', [SettingsController::class, 'getSettingVideos']);
        Route::delete('videos/{id}', [SettingsController::class, 'deleteSettingVideos']);
    });





    Route::get('qtap_clients', [QtapClientsController::class, 'index']);


    //-------------admin--------
    Route::resource('qtap_admins', QtapAdminsController::class);
});


//------------------------------------------------PUBLIC API------------------------------------------------------------
Route::get('products', [ProductsController::class, 'index']);
Route::get('discount', [DiscountController::class, 'index']);

Route::post('cancel_order/{id}', [OrdersController::class, 'cancel_order'])->name('cancel_order');



Route::resource('faq_qtap', FaqQtapController::class);


Route::get('feedback_restaurant', [FeedbackRestaurantController::class, 'index']);
Route::post('feedback_restaurant', [FeedbackRestaurantController::class, 'store']);
Route::put('feedback_restaurant/{id}', [FeedbackRestaurantController::class, 'update']);
Route::delete('feedback_restaurant/{id}', [FeedbackRestaurantController::class, 'destroy']);


//------------get_brunchs-----------------
Route::get('get_brunchs', [QtapClientsController::class, 'get_brunchs']);

//-------------chat--------
Route::resource('chat', ChatController::class);

//-------------customer_info- for chat-------
Route::post('customer_info', [CustomerInfoController::class, 'store']);

//-------------orders--------
Route::post('add_orders', [OrdersController::class, 'store'])->name('add_orders');


##by_majd
Route::post('checkDiscountStatus/{branch_id}', [OrdersController::class, 'checkDiscountStatus']);
Route::post('checkPlanStatus', [CoupPlanController::class, 'checkDiscountStatus']);

//-------------menu--------
Route::get('menu/{id}', [QtapClientsController::class, 'menu'])->name('menu');

Route::get('menu_all_restaurants', [QtapClientsController::class, 'menu_all_restaurants']);


Route::get('menu_by_table/{table}/{id}', [QtapClientsController::class, 'menu_by_table'])->name('menu_by_table');



//-------------add_affiliate--------
Route::post('add_affiliate', [QtapAffiliateController::class, 'store'])->name('add_affiliate');
Route::get('Sales_clicks/{year}', [QtapAffiliateController::class, 'Sales_clicks'])->name('Sales_clicks');



Route::middleware(['auth:restaurant_user_staff', 'role:chef'])->group(function () {
    Route::get('/kitchen/orders', function () {
        return response()->json(['message' => 'Welcome admin Staff!']);
    });
});





//------------------CLIENT -------------------
// Route::middleware('auth:qtap_clients')->group(function () {
Route::middleware('auth:restaurant_user_staff', 'role:admin|chef')->group(function () {


    Route::post('accept_order', [OrdersProcessingController::class, 'accept_order']);
    Route::get('get_new_orders', [OrdersProcessingController::class, 'get_new_orders']);
    Route::post('order_prepared', [OrdersProcessingController::class, 'order_prepared']);
});


Route::middleware('auth:restaurant_user_staff', 'role:admin|cashier')->group(function () {

    Route::post('payment_received', [OrdersProcessingController::class, 'payment_received']);
    Route::get('get_accepted_orders', [OrdersProcessingController::class, 'get_accepted_orders']);
});


Route::middleware('auth:restaurant_user_staff', 'role:admin|waiter')->group(function () {

    Route::post('order_served', [OrdersProcessingController::class, 'order_served']);
    Route::get('get_prepared_orders', [OrdersProcessingController::class, 'get_prepared_orders']);
});




Route::middleware('auth:restaurant_user_staff', 'role:delivery_rider')->group(function () {

    Route::post('order_delivered', [OrdersProcessingController::class, 'order_delivered']);

    Route::post('update_delivery_status', [DeliveryRiderController::class, 'update'])->name('update_delivery_status');
});


Route::middleware(['auth:restaurant_user_staff', 'role:admin|delivery_rider'])->group(function () {

    Route::get('Total_Delivered_Orders/{id}', [DeliveryRiderController::class, 'Total_Delivered_Orders']);
    Route::get('Daily_Delivered_Orders/{id}', [DeliveryRiderController::class, 'Daily_Delivered_Orders']);
    Route::get('Daily_Cancaled_Orders/{id}', [DeliveryRiderController::class, 'Daily_Cancaled_Orders']);

    Route::get('get_prepared_orders_delivery', [OrdersProcessingController::class, 'get_prepared_orders_delivery']);



    Route::post('orders', [DeliveryRiderController::class, 'orders']);
});


Route::get('order_map/{order_id}', [OrdersProcessingController::class, 'order_map']);


Route::middleware('auth:restaurant_user_staff', 'role:admin|cashier|waiter|delivery_rider|chef')->group(function () {

    Route::get('get_proccessing_orders/{id}', [OrdersProcessingController::class, 'get_proccessing_orders']);
});


Route::middleware('auth:restaurant_user_staff', 'role:admin|cashier')->group(function () {
    Route::resource('tables', TablesController::class);

    Route::resource('meals_categories', MealsCategoriesController::class);

    Route::resource('meals_discount', MealsDiscountController::class);

    Route::post('meals/{id}', [MealsController::class, 'update']);
    Route::resource('meals', MealsController::class);

    Route::resource('meals_size', MealsSizeController::class);
    Route::resource('meals_variants', MealsVariantsController::class);
    Route::resource('meals_extra', MealsExtraController::class);


    Route::resource('meals_special_offers', MealsSpecialOffersController::class);
});



Route::middleware('auth:restaurant_user_staff', 'role:admin')->group(function () {



    Route::post('Performance_restaurant/{startYear}-{endYear}', [QtapClientsController::class, 'Performance_restaurant'])->name('Performance_restaurant');




    Route::get('clients_transactions', [ClientsTransactionsController::class, 'index']);

    Route::get('ticket', [TicketSupportController::class, 'index']);
    Route::post('ticket/{id}', [TicketSupportController::class, 'update']);
    Route::delete('ticket/{id}', [TicketSupportController::class, 'destroy']);
    Route::post('ticket', [TicketSupportController::class, 'store']);



    Route::post('feedback_client', [FeedbackController::class, 'store']);




    //-----------------------------------------------------------------------------


    Route::post('order_done', [OrdersProcessingController::class, 'order_done']);

    Route::get('get_served_orders', [OrdersProcessingController::class, 'get_served_orders']);
    Route::get('get_delivery_available', [DeliveryRiderController::class, 'get_delivery_available']);

    Route::post('get_delivered_orders', [OrdersProcessingController::class, 'get_delivered_orders']);

    Route::post('choose_delivery', [OrdersProcessingController::class, 'choose_delivery']);





    //----------------------Deliver---------------------------------------------------------------
    Route::resource('delivery', DeliveryRiderController::class);


    //-----------------------dashboard--------------------------
    Route::get('dashboard/{id}', [QtapClientsController::class, 'dashboard']);

    Route::post('Sales_restaurant/{id}', [QtapClientsController::class, 'Sales_restaurant'])->name('Sales_restaurant');
    Route::get('wallet_restaurant/{id}/{year}', [QtapClientsController::class, 'wallet_restaurant'])->name('wallet_restaurant');
    Route::post('Sales_by_days_restaurant/{id}', [QtapClientsController::class, 'Sales_by_days_restaurant'])->name('Sales_by_days_restaurant');

    Route::get('Customer_log/{id}/{year1}/{year2}', [QtapClientsController::class, 'Customer_log'])->name('Customer_log');

    //----------------------RestaurantUserStaff---------------------------------------------------------------

    Route::get('restaurant_user_staff/{brunch_id}', [RestaurantUserStaffController::class, 'index']);

    Route::get('resturant_users/{brunch_id}', [RestaurantUserStaffController::class, 'resturant_users']);

    Route::put('restaurant_user_staff/{id}', [RestaurantUserStaffController::class, 'update']);

    Route::put('link_user_role/{id}', [RestaurantUserStaffController::class, 'link_user_role']);

    Route::delete('restaurant_user_staff/{id}', [RestaurantUserStaffController::class, 'destroy']);
    Route::post('restaurant_user_staff', [RestaurantUserStaffController::class, 'store']);





    //----------------------Orders---------------------------------------------------------------
    Route::get('orders/{id}', [OrdersController::class, 'index'])->name('orders');
    Route::delete('orders/{id}', [OrdersController::class, 'destroy'])->name('orders');




    Route::resource('delivery_area', DeliveryAreaController::class);

    Route::post('logout', [AuthController::class, 'logout']);


    //-----------get_info------------------------------

    Route::get('get_info', [QtapClientsController::class, 'get_info']);




    //------------restaurant_staff-----
    Route::resource('restaurant_staff', RestaurantStaffController::class);


    //------------roles-----
    Route::resource('roles', RoleController::class);

    //------------users_staff-----
    Route::resource('restaurant_users', RestaurantUsersController::class);






    Route::resource('payment', PaymentController::class);


    Route::get('area', [TablesController::class, 'get_area']);
    Route::post('area', [TablesController::class, 'store_area']);
    Route::post('area/{id}', [TablesController::class, 'update_area']);
    Route::delete('area/{id}', [TablesController::class, 'delete_area']);
});


//------------------add qtap_affiliate--------
Route::post('add_qtap_affiliate', [QtapAffiliateController::class, 'store']);


//------------------affiliate-----------------
Route::middleware('auth:qtap_affiliate')->group(function () {

    Route::get('affiliate_transactions/{id}', [QtapAffiliateController::class, 'affiliate_transactions']);

    Route::get('get_sales_affiliate', [QtapAffiliateController::class, 'get_sales_affiliate'])->name('get_sales_affiliate');
    Route::get('get_myinfo', [QtapAffiliateController::class, 'get_myinfo'])->name('get_myinfo');
});


//------------------add qtap_clients--------
Route::post('qtap_clients', [QtapClientsController::class, 'store']);



Route::get('/governorates', [GovernorateController::class, 'getGovernorates']);
Route::get('/governorates/cities/{governorateId}', [GovernorateController::class, 'getCitiesByGovernorate']);


Route::get('/all_currencies', [CurrencyController::class, 'index']);
Route::get('tables/{brunch_id}', [TablesController::class, 'index']);
