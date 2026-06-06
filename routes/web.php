<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminDiseaseTrackingController;
use App\Http\Controllers\AdminFertilizerTrackingController;
use App\Http\Controllers\AdminHarvestTrackingController;
use App\Http\Controllers\AdminMillTrackingController;
use App\Http\Controllers\AdminPestTrackingController;
use App\Http\Controllers\AdminPrepTrackingController;
use App\Http\Controllers\AdminPasswordResetController;
use App\Http\Controllers\AdminWaterTrackingController;
use App\Http\Controllers\RiceIssueReportController;
use App\Http\Controllers\RiceVarietyController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SrpAssessmentController;
use App\Http\Controllers\SystemIssueReportController;
use App\Http\Controllers\TrackingAdviceController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('admin.guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'create']);
    Route::post('/admin/login', [AdminAuthController::class, 'store']);
    Route::get('/admin/forgot-password', [AdminPasswordResetController::class, 'requestForm'])->name('admin.password.request');
    Route::post('/admin/forgot-password', [AdminPasswordResetController::class, 'sendResetLink'])->name('admin.password.email');
    Route::get('/admin/reset-password/{token}', [AdminPasswordResetController::class, 'resetForm'])->name('admin.password.reset');
    Route::post('/admin/reset-password', [AdminPasswordResetController::class, 'reset'])->name('admin.password.update');
});

Route::middleware('admin.auth')->group(function (): void {
    Route::post('/admin/logout', [AdminAuthController::class, 'destroy']);

    Route::middleware('admin.menu:dashboard')->group(function (): void {
        Route::get('/admin', [AdminDashboardController::class, 'index'])->middleware('admin.action:dashboard,view');
        Route::get('/admin/followup-plans/create', [AdminDashboardController::class, 'createFollowupPlan'])->middleware('admin.action:dashboard,manage');
        Route::post('/admin/followup-plans', [AdminDashboardController::class, 'storeFollowupPlan'])->middleware('admin.action:dashboard,manage');
        Route::post('/admin/dashboard-work-items/{dashboardWorkItem}/toggle-followup', [AdminDashboardController::class, 'toggleFollowup'])->middleware('admin.action:dashboard,manage');
        Route::get('/admin/alerts', [AdminDashboardController::class, 'alerts'])->middleware('admin.action:dashboard,view');
        Route::post('/admin/alerts/mark-read', [AdminDashboardController::class, 'markAlertsRead'])->middleware('admin.action:dashboard,manage');
        Route::get('/admin/activity', [AdminDashboardController::class, 'activity'])->middleware('admin.action:dashboard,view');
        Route::get('/admin/dashboard/today-tasks', [AdminDashboardController::class, 'todayTasks'])->middleware('admin.action:dashboard,view');
        Route::get('/admin/dashboard/issue-reports', [AdminDashboardController::class, 'issueReports'])->middleware('admin.action:dashboard,view');
        Route::get('/admin/dashboard/document-reviews', [AdminDashboardController::class, 'documentReviews'])->middleware('admin.action:dashboard,view');
        Route::get('/admin/dashboard/all-issues', [AdminDashboardController::class, 'allIssues'])->middleware('admin.action:dashboard,view');
        Route::get('/admin/report/export/print', [AdminDashboardController::class, 'printSummary'])->middleware('admin.action:dashboard,view');
    });

    Route::get('/admin/users', fn () => redirect('/admin/farmer-users'));
    Route::get('/admin/users/create', fn () => redirect('/admin/farmer-users/create'));
    Route::get('/admin/users/account', fn () => redirect('/admin/farmer-users/create'));
    Route::post('/admin/users/account', fn () => redirect('/admin/farmer-users/create'));
    Route::get('/admin/users/{user}', fn (string $user) => redirect('/admin/farmer-users/' . $user));
    Route::get('/admin/users/{user}/edit', fn (string $user) => redirect('/admin/farmer-users/' . $user . '/edit'));

    Route::middleware('admin.menu:farmer_users')->group(function (): void {
        Route::get('/admin/farmer-users', [UserManagementController::class, 'index'])->middleware('admin.action:farmer_users,view');
        Route::get('/admin/farmer-users/create', [UserManagementController::class, 'create'])->middleware('admin.action:farmer_users,create');
        Route::post('/admin/farmer-users/create', [UserManagementController::class, 'storeDraft'])->middleware('admin.action:farmer_users,create');
        Route::get('/admin/farmer-users/account', [UserManagementController::class, 'account'])->middleware('admin.action:farmer_users,create');
        Route::post('/admin/farmer-users/account', [UserManagementController::class, 'store'])->middleware('admin.action:farmer_users,create');
        Route::get('/admin/farmer-users/{user}', [UserManagementController::class, 'show'])->middleware('admin.action:farmer_users,view');
        Route::get('/admin/farmer-users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('admin.action:farmer_users,edit');
        Route::put('/admin/farmer-users/{user}', [UserManagementController::class, 'update'])->middleware('admin.action:farmer_users,edit');
        Route::post('/admin/farmer-users/{user}/delete', [UserManagementController::class, 'destroy'])->middleware('admin.action:farmer_users,delete');
        Route::get('/admin/farmer-users/{user}/plots/create', [UserManagementController::class, 'createPlot'])->middleware('admin.action:farmer_users,edit');
        Route::post('/admin/farmer-users/{user}/plots', [UserManagementController::class, 'storePlot'])->middleware('admin.action:farmer_users,edit');
    });

    Route::middleware('admin.menu:admin_users')->group(function (): void {
        Route::get('/admin/admin-users', [UserManagementController::class, 'adminIndex'])->middleware('admin.action:admin_users,view');
        Route::get('/admin/admin-users/create', [UserManagementController::class, 'createAdmin'])->middleware('admin.action:admin_users,create');
        Route::post('/admin/admin-users', [UserManagementController::class, 'storeAdmin'])->middleware('admin.action:admin_users,create');
        Route::get('/admin/admin-users/{user}', [UserManagementController::class, 'showAdmin'])->middleware('admin.action:admin_users,view');
        Route::get('/admin/admin-users/{user}/edit', [UserManagementController::class, 'editAdmin'])->middleware('admin.action:admin_users,edit');
        Route::put('/admin/admin-users/{user}', [UserManagementController::class, 'updateAdmin'])->middleware('admin.action:admin_users,edit');
        Route::post('/admin/admin-users/{user}/delete', [UserManagementController::class, 'destroyAdmin'])->middleware('admin.action:admin_users,delete');
    });

    Route::middleware('admin.menu:roles')->group(function (): void {
        Route::get('/admin/roles', [RoleManagementController::class, 'index'])->middleware('admin.action:roles,view');
        Route::get('/admin/roles/create', [RoleManagementController::class, 'create'])->middleware('admin.action:roles,manage');
        Route::post('/admin/roles', [RoleManagementController::class, 'store'])->middleware('admin.action:roles,manage');
        Route::get('/admin/roles/{role}/edit', [RoleManagementController::class, 'edit'])->middleware('admin.action:roles,manage');
        Route::put('/admin/roles/{role}', [RoleManagementController::class, 'update'])->middleware('admin.action:roles,manage');
        Route::post('/admin/roles/{role}/delete', [RoleManagementController::class, 'destroy'])->middleware('admin.action:roles,manage');
    });

    Route::middleware('admin.menu:rice')->group(function (): void {
        Route::get('/admin/rice', [RiceVarietyController::class, 'index'])->middleware('admin.action:rice,view');
        Route::get('/admin/rice/create', [RiceVarietyController::class, 'create'])->middleware('admin.action:rice,create');
        Route::post('/admin/rice', [RiceVarietyController::class, 'store'])->middleware('admin.action:rice,create');
        Route::get('/admin/rice/{riceVariety}/edit', [RiceVarietyController::class, 'edit'])->middleware('admin.action:rice,edit');
        Route::put('/admin/rice/{riceVariety}', [RiceVarietyController::class, 'update'])->middleware('admin.action:rice,edit');
        Route::delete('/admin/rice/{riceVariety}', [RiceVarietyController::class, 'destroy'])->middleware('admin.action:rice,delete');
        Route::post('/admin/rice/{riceVariety}/delete', [RiceVarietyController::class, 'destroy'])->middleware('admin.action:rice,delete');
        Route::post('/admin/rice/{riceVariety}/restore', [RiceVarietyController::class, 'restore'])->middleware('admin.action:rice,delete');
        Route::post('/admin/rice/{riceVariety}/force-delete', [RiceVarietyController::class, 'forceDestroy'])->middleware('admin.action:rice,delete');
    });

    Route::middleware('admin.menu:srp_manual')->group(function (): void {
        Route::get('/admin/srp', function () {
            return view('admin.srp');
        })->middleware('admin.action:srp_manual,view');
    });

    Route::middleware('admin.menu:srp_farmers')->group(function (): void {
        Route::get('/admin/srp/farmers', [SrpAssessmentController::class, 'index'])->middleware('admin.action:srp_farmers,view');
        Route::get('/admin/srp/farmers/passed', [SrpAssessmentController::class, 'passed'])->middleware('admin.action:srp_farmers,view');
        Route::get('/admin/srp/farmers/{farmer}/plots/{plot}', [SrpAssessmentController::class, 'plotOverview'])->middleware('admin.action:srp_farmers,view');
        Route::get('/admin/srp/farmers/{farmer}', [SrpAssessmentController::class, 'show'])->middleware('admin.action:srp_farmers,view');
    });

    Route::middleware('admin.menu:settings')->group(function (): void {
        Route::get('/admin/settings', [SettingController::class, 'edit'])->middleware('admin.action:settings,view');
        Route::post('/admin/settings', [SettingController::class, 'update'])->middleware('admin.action:settings,edit');
    });

    Route::middleware('admin.menu:report_rice')->group(function (): void {
        Route::get('/admin/report/rice', [RiceIssueReportController::class, 'index'])->middleware('admin.action:report_rice,view');
        Route::get('/admin/report/rice/print', [RiceIssueReportController::class, 'print'])->middleware('admin.action:report_rice,export');
        Route::get('/admin/report/rice/detail', [RiceIssueReportController::class, 'show'])->middleware('admin.action:report_rice,view');
    });

    Route::middleware('admin.menu:report_system')->group(function (): void {
        Route::get('/admin/report/system', [SystemIssueReportController::class, 'index'])->middleware('admin.action:report_system,view');
        Route::get('/admin/report/system/print', [SystemIssueReportController::class, 'print'])->middleware('admin.action:report_system,view');
        Route::get('/admin/report/system/detail', [SystemIssueReportController::class, 'show'])->middleware('admin.action:report_system,view');
        Route::post('/admin/report/system/{ticket}/delete', [SystemIssueReportController::class, 'destroy'])->middleware('admin.action:report_system,delete');
    });

    Route::middleware('admin.menu:tracking_prep')->group(function (): void {
        Route::get('/admin/tracking/prep', [AdminPrepTrackingController::class, 'index'])->middleware('admin.action:tracking_prep,view');
        Route::get('/admin/tracking/prep/detail', [AdminPrepTrackingController::class, 'show'])->middleware('admin.action:tracking_prep,view');
        Route::get('/admin/tracking/prep/detail/{prepTrackingActivity}', [AdminPrepTrackingController::class, 'show'])->middleware('admin.action:tracking_prep,view');
        Route::post('/admin/tracking/prep/{prepTrackingActivity}/status', [AdminPrepTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_prep,manage');
        Route::post('/admin/tracking/prep/{prepTrackingActivity}/delete', [AdminPrepTrackingController::class, 'destroy'])->middleware('admin.action:tracking_prep,manage');
        Route::get('/admin/tracking/prep/print', [AdminPrepTrackingController::class, 'print'])->middleware('admin.action:tracking_prep,export');
    });

    Route::middleware('admin.menu:tracking_water')->group(function (): void {
        Route::get('/admin/tracking/water', [AdminWaterTrackingController::class, 'index'])->middleware('admin.action:tracking_water,view');
        Route::get('/admin/tracking/water/detail', [AdminWaterTrackingController::class, 'show'])->middleware('admin.action:tracking_water,view');
        Route::get('/admin/tracking/water/detail/{waterTrackingActivity}', [AdminWaterTrackingController::class, 'show'])->middleware('admin.action:tracking_water,view');
        Route::post('/admin/tracking/water/{waterTrackingActivity}/status', [AdminWaterTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_water,manage');
        Route::post('/admin/tracking/water/{waterTrackingActivity}/delete', [AdminWaterTrackingController::class, 'destroy'])->middleware('admin.action:tracking_water,manage');
        Route::get('/admin/tracking/water/print', [AdminWaterTrackingController::class, 'print'])->middleware('admin.action:tracking_water,export');
    });

    Route::middleware('admin.menu:tracking_fertilizer')->group(function (): void {
        Route::get('/admin/tracking/fertilizer', [AdminFertilizerTrackingController::class, 'index'])->middleware('admin.action:tracking_fertilizer,view');
        Route::get('/admin/tracking/fertilizer/detail', [AdminFertilizerTrackingController::class, 'show'])->middleware('admin.action:tracking_fertilizer,view');
        Route::get('/admin/tracking/fertilizer/detail/{fertilizerTrackingActivity}', [AdminFertilizerTrackingController::class, 'show'])->middleware('admin.action:tracking_fertilizer,view');
        Route::post('/admin/tracking/fertilizer/{fertilizerTrackingActivity}/status', [AdminFertilizerTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_fertilizer,manage');
        Route::post('/admin/tracking/fertilizer/{fertilizerTrackingActivity}/delete', [AdminFertilizerTrackingController::class, 'destroy'])->middleware('admin.action:tracking_fertilizer,manage');
        Route::get('/admin/tracking/fertilizer/print', [AdminFertilizerTrackingController::class, 'print'])->middleware('admin.action:tracking_fertilizer,export');
    });

    Route::middleware('admin.menu:tracking_pest')->group(function (): void {
        Route::get('/admin/tracking/pest', [AdminPestTrackingController::class, 'index'])->middleware('admin.action:tracking_pest,view');
        Route::get('/admin/tracking/pest/detail', [AdminPestTrackingController::class, 'show'])->middleware('admin.action:tracking_pest,view');
        Route::get('/admin/tracking/pest/detail/{pestTrackingActivity}', [AdminPestTrackingController::class, 'show'])->middleware('admin.action:tracking_pest,view');
        Route::post('/admin/tracking/pest/{pestTrackingActivity}/status', [AdminPestTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_pest,manage');
        Route::post('/admin/tracking/pest/{pestTrackingActivity}/delete', [AdminPestTrackingController::class, 'destroy'])->middleware('admin.action:tracking_pest,manage');
        Route::get('/admin/tracking/pest/print', [AdminPestTrackingController::class, 'print'])->middleware('admin.action:tracking_pest,export');
    });

    Route::middleware('admin.menu:tracking_disease')->group(function (): void {
        Route::get('/admin/tracking/disease', [AdminDiseaseTrackingController::class, 'index'])->middleware('admin.action:tracking_disease,view');
        Route::get('/admin/tracking/disease/detail', [AdminDiseaseTrackingController::class, 'show'])->middleware('admin.action:tracking_disease,view');
        Route::get('/admin/tracking/disease/detail/{diseaseTrackingActivity}', [AdminDiseaseTrackingController::class, 'show'])->middleware('admin.action:tracking_disease,view');
        Route::post('/admin/tracking/disease/{diseaseTrackingActivity}/status', [AdminDiseaseTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_disease,manage');
        Route::post('/admin/tracking/disease/{diseaseTrackingActivity}/delete', [AdminDiseaseTrackingController::class, 'destroy'])->middleware('admin.action:tracking_disease,manage');
        Route::get('/admin/tracking/disease/print', [AdminDiseaseTrackingController::class, 'print'])->middleware('admin.action:tracking_disease,export');
    });

    Route::middleware('admin.menu:tracking_harvest')->group(function (): void {
        Route::get('/admin/tracking/harvest', [AdminHarvestTrackingController::class, 'index'])->middleware('admin.action:tracking_harvest,view');
        Route::get('/admin/tracking/harvest/detail', [AdminHarvestTrackingController::class, 'show'])->middleware('admin.action:tracking_harvest,view');
        Route::get('/admin/tracking/harvest/detail/{harvestTrackingActivity}', [AdminHarvestTrackingController::class, 'show'])->middleware('admin.action:tracking_harvest,view');
        Route::post('/admin/tracking/harvest/{harvestTrackingActivity}/status', [AdminHarvestTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_harvest,manage');
        Route::post('/admin/tracking/harvest/{harvestTrackingActivity}/delete', [AdminHarvestTrackingController::class, 'destroy'])->middleware('admin.action:tracking_harvest,manage');
        Route::get('/admin/tracking/harvest/print', [AdminHarvestTrackingController::class, 'print'])->middleware('admin.action:tracking_harvest,export');
    });

    Route::middleware('admin.menu:tracking_mill')->group(function (): void {
        Route::get('/admin/tracking/mill', [AdminMillTrackingController::class, 'index'])->middleware('admin.action:tracking_mill,view');
        Route::get('/admin/tracking/mill/detail', [AdminMillTrackingController::class, 'show'])->middleware('admin.action:tracking_mill,view');
        Route::get('/admin/tracking/mill/detail/{millTrackingActivity}', [AdminMillTrackingController::class, 'show'])->middleware('admin.action:tracking_mill,view');
        Route::post('/admin/tracking/mill/{millTrackingActivity}/status', [AdminMillTrackingController::class, 'updateStatus'])->middleware('admin.action:tracking_mill,manage');
        Route::post('/admin/tracking/mill/{millTrackingActivity}/delete', [AdminMillTrackingController::class, 'destroy'])->middleware('admin.action:tracking_mill,manage');
        Route::get('/admin/tracking/mill/print', [AdminMillTrackingController::class, 'print'])->middleware('admin.action:tracking_mill,export');
        Route::post('/admin/tracking-advice/{pageKey}', [TrackingAdviceController::class, 'store'])->middleware('admin.action:tracking_mill,manage');
    });
});
