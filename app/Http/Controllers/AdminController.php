<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Program;
use App\Link;
use App\Click;
use App\Conversion;
use App\Commission;
use App\Product;
use App\ProductCommission;
use App\UserPoints;
use App\PointsTransaction;
use App\CashbackSetting;
use App\Referral;
use App\PointsRedemption;
use App\Gift;
use App\Services\ProductService;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    protected ProductService $productService;
    protected PointsService $pointsService;

    public function __construct(ProductService $productService, PointsService $pointsService)
    {
        $this->productService = $productService;
        $this->pointsService = $pointsService;
    }
    /**
     * Display HTML dashboard view
     */
    public function dashboardView()
    {
        $stats = [
            'total_users' => User::count(),
            'total_programs' => Program::count(),
            'total_links' => Link::count(),
            'total_clicks' => Click::count(),
            'total_conversions' => Conversion::count(),
            'total_commissions' => Commission::sum('amount') ?? 0,
            'pending_commissions' => Commission::where('status', Commission::STATUS_PENDING)->sum('amount') ?? 0,
            'paid_commissions' => Commission::where('status', Commission::STATUS_PAID)->sum('amount') ?? 0,
        ];

        $recentConversions = Conversion::with(['user', 'program', 'link'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentClicks = Click::with(['user', 'program', 'link'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topPrograms = Program::withCount(['conversions', 'clicks'])
            ->orderBy('conversions_count', 'desc')
            ->limit(5)
            ->get();

        $topUsers = User::withCount(['conversions', 'clicks'])
            ->orderBy('conversions_count', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentConversions', 'recentClicks', 'topPrograms', 'topUsers'));
    }

    /**
     * Display users management view
     */
    public function usersView(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['links', 'clicks', 'conversions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users', compact('users'));
    }

    /**
     * Show create user form
     */
    public function createUserView()
    {
        $users = User::where('role', '!=', 'admin')->get();
        return view('admin.users.create', compact('users'));
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,affiliate,sub_affiliate',
            'parent_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'parent_id' => $request->parent_id,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully!');
    }

    /**
     * Show edit user form
     */
    public function editUserView(User $user)
    {
        $users = User::where('role', '!=', 'admin')->where('id', '!=', $user->id)->get();
        return view('admin.users.edit', compact('user', 'users'));
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,affiliate,sub_affiliate',
            'parent_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'parent_id' => $request->parent_id,
            'is_active' => $request->has('is_active') ? true : false,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users')->with('success', 'User updated successfully!');
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account!');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully!');
    }

    /**
     * Display programs management view
     */
    public function programsView(Request $request)
    {
        $query = Program::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $programs = $query->withCount(['links', 'clicks', 'conversions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.programs', compact('programs'));
    }

    /**
     * Show create program form
     */
    public function createProgramView()
    {
        return view('admin.programs.create');
    }

    /**
     * Store new program
     */
    public function storeProgram(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:programs,slug',
            'type' => 'required|in:ecommerce,finance,referral,app_download,other',
            'merchant_name' => 'required|string|max:255',
            'merchant_url' => 'required|url',
            'status' => 'required|in:active,inactive,suspended',
            'logo_file' => 'nullable|image|max:2048',
            'logo_url' => 'nullable|url',
            'supports_sub_affiliate' => 'nullable|boolean',
        ]);

        $logoUrl = $request->logo_url;
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('public/logos');
            $logoUrl = '/storage/' . ltrim(str_replace('public/', '', $path), '/');
        }

        Program::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'type' => $request->type,
            'description' => $request->input('description'),
            'merchant_name' => $request->merchant_name,
            'merchant_url' => $request->merchant_url,
            'logo_url' => $logoUrl,
            'status' => $request->status,
            'commission_structure' => $request->input('commission_structure', '{}'),
            'supports_sub_affiliate' => (bool)$request->supports_sub_affiliate,
            'tracking_parameters' => $request->input('tracking_parameters', null),
        ]);

        return redirect()->route('admin.programs')->with('success', 'Program created successfully!');
    }

    /**
     * Show edit program form
     */
    public function editProgramView(Program $program)
    {
        return view('admin.programs.edit', compact('program'));
    }

    /**
     * Update program
     */
    public function updateProgram(Request $request, Program $program)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:programs,slug,' . $program->id,
            'type' => 'required|in:ecommerce,finance,referral,app_download,other',
            'merchant_name' => 'required|string|max:255',
            'merchant_url' => 'required|url',
            'status' => 'required|in:active,inactive,suspended',
            'logo_file' => 'nullable|image|max:2048',
            'logo_url' => 'nullable|url',
            'supports_sub_affiliate' => 'nullable|boolean',
        ]);

        $logoUrl = $request->logo_url ?? $program->logo_url;
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('public/logos');
            $logoUrl = '/storage/' . ltrim(str_replace('public/', '', $path), '/');
        }

        $program->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'type' => $request->type,
            'description' => $request->input('description'),
            'merchant_name' => $request->merchant_name,
            'merchant_url' => $request->merchant_url,
            'logo_url' => $logoUrl,
            'status' => $request->status,
            'commission_structure' => $request->input('commission_structure', '{}'),
            'supports_sub_affiliate' => (bool)$request->supports_sub_affiliate,
            'tracking_parameters' => $request->input('tracking_parameters', null),
        ]);

        return redirect()->route('admin.programs')->with('success', 'Program updated successfully!');
    }

    /**
     * Delete program
     */
    public function deleteProgram(Program $program)
    {
        $program->delete();
        return redirect()->route('admin.programs')->with('success', 'Program deleted successfully!');
    }

    /**
     * Display links management view
     */
    public function linksView(Request $request)
    {
        $query = Link::with(['user', 'program']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $links = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.links', compact('links'));
    }

    /**
     * Show create link form
     */
    public function createLinkView()
    {
        $users = User::where('role', '!=', 'admin')->get();
        $programs = Program::where('status', 'active')->get();
        return view('admin.links.create', compact('users', 'programs'));
    }

    /**
     * Store new link
     */
    public function storeLink(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'user_id' => 'required|exists:users,id',
            'original_url' => 'required|url',
            'short_code' => 'nullable|string|max:255|unique:links,short_code',
            'sub_id' => 'nullable|string|max:255',
            'campaign_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $shortCode = $request->short_code ?? Link::generateShortCode();
        
        $link = Link::create([
            'program_id' => $request->program_id,
            'user_id' => $request->user_id,
            'original_url' => $request->original_url,
            'affiliate_url' => $request->original_url, // Will be generated properly later
            'short_code' => $shortCode,
            'sub_id' => $request->sub_id,
            'campaign_name' => $request->campaign_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : false,
            'expires_at' => $request->expires_at,
        ]);

        // Generate proper affiliate URL
        $link->affiliate_url = $link->generateAffiliateUrl();
        $link->save();

        return redirect()->route('admin.links')->with('success', 'Link created successfully!');
    }

    /**
     * Show edit link form
     */
    public function editLinkView(Link $link)
    {
        $users = User::where('role', '!=', 'admin')->get();
        $programs = Program::where('status', 'active')->get();
        return view('admin.links.edit', compact('link', 'users', 'programs'));
    }

    /**
     * Update link
     */
    public function updateLink(Request $request, Link $link)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'user_id' => 'required|exists:users,id',
            'original_url' => 'required|url',
            'short_code' => 'nullable|string|max:255|unique:links,short_code,' . $link->id,
            'sub_id' => 'nullable|string|max:255',
            'campaign_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $link->update([
            'program_id' => $request->program_id,
            'user_id' => $request->user_id,
            'original_url' => $request->original_url,
            'short_code' => $request->short_code ?? $link->short_code,
            'sub_id' => $request->sub_id,
            'campaign_name' => $request->campaign_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : false,
            'expires_at' => $request->expires_at,
        ]);

        // Regenerate affiliate URL
        $link->affiliate_url = $link->generateAffiliateUrl();
        $link->save();

        return redirect()->route('admin.links')->with('success', 'Link updated successfully!');
    }

    /**
     * Delete link
     */
    public function deleteLink(Link $link)
    {
        $link->delete();
        return redirect()->route('admin.links')->with('success', 'Link deleted successfully!');
    }

    /**
     * Toggle link active status
     */
    public function toggleLinkStatus(Link $link)
    {
        $link->is_active = !$link->is_active;
        $link->save();
        
        return redirect()->route('admin.links')->with('success', 'Link status updated!');
    }

    /**
     * Show API testing view
     */
    public function apiTestView()
    {
        $links = Link::with(['user', 'program'])->where('is_active', true)->get();
        $clicks = Click::with(['link', 'user'])->where('is_converted', false)->orderBy('created_at', 'desc')->limit(50)->get();
        return view('admin.api-test', compact('links', 'clicks'));
    }

    /**
     * Test click API
     */
    public function testClickApi(Request $request)
    {
        $request->validate([
            'link_id' => 'required|exists:links,id',
        ]);

        // Simulate a click request
        $link = Link::findOrFail($request->link_id);
        
        $clickData = [
            'link_id' => $link->id,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'Mozilla/5.0 (Test)',
            'referrer' => $request->header('referer'),
        ];

        // Call the API controller method
        $apiController = new \App\Http\Controllers\ApiController();
        $response = $apiController->trackClick(new \Illuminate\Http\Request($clickData));

        return response()->json([
            'success' => $response->getStatusCode() === 200,
            'data' => json_decode($response->getContent(), true),
        ]);
    }

    /**
     * Test conversion API
     */
    public function testConversionApi(Request $request)
    {
        $request->validate([
            'click_id' => 'required|exists:clicks,id',
            'event_type' => 'required|in:purchase,signup,download,install,lead,click,other',
            'conversion_value' => 'nullable|numeric|min:0',
        ]);

        $conversionData = [
            'click_id' => $request->click_id,
            'event_type' => $request->event_type,
            'conversion_value' => $request->conversion_value ?? 1000,
            'currency' => $request->currency ?? 'INR',
            'order_id' => $request->order_id ?? 'TEST-' . time(),
        ];

        // Call the API controller method
        $apiController = new \App\Http\Controllers\ApiController();
        $response = $apiController->reportConversion(new \Illuminate\Http\Request($conversionData));

        return response()->json([
            'success' => $response->getStatusCode() === 200,
            'data' => json_decode($response->getContent(), true),
        ]);
    }

    /**
     * Display clicks view
     */
    public function clicksView(Request $request)
    {
        $query = Click::with(['user', 'program', 'link']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        if ($request->has('converted')) {
            $query->where('converted', $request->converted);
        }

        $clicks = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.clicks', compact('clicks'));
    }

    /**
     * Display conversions view
     */
    public function conversionsView(Request $request)
    {
        $query = Conversion::with(['user', 'program', 'link', 'click']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $conversions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.conversions', compact('conversions'));
    }

    /**
     * Display commissions view
     */
    public function commissionsView(Request $request)
    {
        $query = Commission::with(['user', 'conversion']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('commission_type', $request->type);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $commissions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        $users = User::where('role', '!=', 'admin')->get();
        return view('admin.commissions', compact('commissions', 'users'));
    }

    /**
     * Approve commission
     */
    public function approveCommission(Commission $commission)
    {
        $commission->approve();
        return redirect()->route('admin.commissions')->with('success', 'Commission approved successfully!');
    }

    /**
     * Reject/Cancel commission
     */
    public function rejectCommission(Commission $commission)
    {
        $commission->cancel();
        return redirect()->route('admin.commissions')->with('success', 'Commission cancelled successfully!');
    }

    /**
     * Mark commission as paid
     */
    public function markCommissionPaid(Request $request, Commission $commission)
    {
        $request->validate([
            'payout_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $commission->update([
            'status' => Commission::STATUS_PAID,
            'paid_at' => now(),
            'payout_method' => $request->payout_method ?? Commission::PAYOUT_BANK_TRANSFER,
            'payout_details' => [
                'transaction_id' => $request->transaction_id,
                'notes' => $request->notes,
            ],
        ]);

        return redirect()->route('admin.commissions')->with('success', 'Commission marked as paid!');
    }

    /**
     * Display analytics view
     */
    public function analyticsView(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $clicksOverTime = Click::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $conversionsOverTime = Conversion::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topCountries = Click::select('country', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $deviceTypes = Click::select('device_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->get();

        return view('admin.analytics', compact('clicksOverTime', 'conversionsOverTime', 'topCountries', 'deviceTypes', 'dateFrom', 'dateTo'));
    }
    /**
     * Display the admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_programs' => Program::count(),
            'total_links' => Link::count(),
            'total_clicks' => Click::count(),
            'total_conversions' => Conversion::count(),
            'total_commissions' => Commission::sum('amount'),
            'pending_commissions' => Commission::where('status', Commission::STATUS_PENDING)->sum('amount'),
            'paid_commissions' => Commission::where('status', Commission::STATUS_PAID)->sum('amount'),
        ];

        // Recent activity
        $recentConversions = Conversion::with(['user', 'program', 'link'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentClicks = Click::with(['user', 'program', 'link'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top performing programs
        $topPrograms = Program::withCount(['conversions', 'clicks'])
            ->orderBy('conversions_count', 'desc')
            ->limit(5)
            ->get();

        // Top performing users
        $topUsers = User::withCount(['conversions', 'clicks'])
            ->orderBy('conversions_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => $stats,
                'recent_conversions' => $recentConversions,
                'recent_clicks' => $recentClicks,
                'top_programs' => $topPrograms,
                'top_users' => $topUsers,
            ]
        ]);
    }

    /**
     * Display all users
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['links', 'clicks', 'conversions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Display all programs
     */
    public function programs(Request $request)
    {
        $query = Program::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $programs = $query->withCount(['links', 'clicks', 'conversions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $programs
        ]);
    }

    /**
     * Display all links
     */
    public function links(Request $request)
    {
        $query = Link::with(['user', 'program']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by program
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $links = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $links
        ]);
    }

    /**
     * Display all clicks
     */
    public function clicks(Request $request)
    {
        $query = Click::with(['user', 'program', 'link']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by program
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        // Filter by conversion status
        if ($request->has('converted')) {
            $query->where('converted', $request->converted);
        }

        $clicks = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $clicks
        ]);
    }

    /**
     * Display all conversions
     */
    public function conversions(Request $request)
    {
        $query = Conversion::with(['user', 'program', 'link', 'click']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by program
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by event type
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $conversions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $conversions
        ]);
    }

    /**
     * Display all commissions
     */
    public function commissions(Request $request)
    {
        $query = Commission::with(['user', 'conversion']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('commission_type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $commissions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $commissions
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        // Clicks over time
        $clicksOverTime = Click::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Conversions over time
        $conversionsOverTime = Conversion::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top countries
        $topCountries = Click::select('country', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Device types
        $deviceTypes = Click::select('device_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'clicks_over_time' => $clicksOverTime,
                'conversions_over_time' => $conversionsOverTime,
                'top_countries' => $topCountries,
                'device_types' => $deviceTypes,
            ]
        ]);
    }

    // ========== PRODUCT MANAGEMENT METHODS ==========

    /**
     * Display products list (Admin)
     */
    public function productsView(Request $request)
    {
        $query = Product::with(['productLinks.program', 'activeCommissions']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Display product commissions (Admin)
     */
    public function productCommissionsView($productId)
    {
        $product = Product::with(['productCommissions.program'])->findOrFail($productId);
        $programs = Program::where('status', Program::STATUS_ACTIVE)->get();
        
        return view('admin.products.commissions', compact('product', 'programs'));
    }

    /**
     * Store product commission (Admin)
     */
    public function storeProductCommission(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'fixed_amount' => 'nullable|numeric|min:0|required_if:commission_type,fixed',
            'category' => 'nullable|string|max:100',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            ProductCommission::updateOrCreate(
                [
                    'product_id' => $productId,
                    'program_id' => $request->program_id,
                ],
                [
                    'commission_rate' => $request->commission_rate,
                    'commission_type' => $request->commission_type,
                    'fixed_amount' => $request->fixed_amount,
                    'category' => $request->category,
                    'min_purchase' => $request->min_purchase ?? 0,
                    'max_commission' => $request->max_commission,
                    'status' => $request->status,
                    'source' => ProductCommission::SOURCE_MANUAL,
                    'last_updated_at' => now(),
                ]
            );

            return redirect()->back()
                ->with('success', 'Commission rate added/updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to store product commission', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to save commission rate')
                ->withInput();
        }
    }

    /**
     * Update product commission (Admin)
     */
    public function updateProductCommission(Request $request, $productId, $commissionId)
    {
        $commission = ProductCommission::where('product_id', $productId)
            ->findOrFail($commissionId);

        $validator = Validator::make($request->all(), [
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'fixed_amount' => 'nullable|numeric|min:0|required_if:commission_type,fixed',
            'category' => 'nullable|string|max:100',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $commission->update([
                'commission_rate' => $request->commission_rate,
                'commission_type' => $request->commission_type,
                'fixed_amount' => $request->fixed_amount,
                'category' => $request->category,
                'min_purchase' => $request->min_purchase ?? 0,
                'max_commission' => $request->max_commission,
                'status' => $request->status,
                'last_updated_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'Commission rate updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update product commission', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update commission rate')
                ->withInput();
        }
    }

    /**
     * Delete product commission (Admin)
     */
    public function deleteProductCommission($productId, $commissionId)
    {
        try {
            $commission = ProductCommission::where('product_id', $productId)
                ->findOrFail($commissionId);
            
            $commission->delete();

            return redirect()->back()
                ->with('success', 'Commission rate deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete product commission', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete commission rate');
        }
    }

    /**
     * Import product commissions from CSV (Admin)
     */
    public function importProductCommissions(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $file = $request->file('csv_file');
            $data = array_map('str_getcsv', file($file->getRealPath()));
            
            // Skip header row
            $header = array_shift($data);
            
            $imported = 0;
            $errors = [];

            foreach ($data as $row) {
                if (count($row) < 3) continue;

                try {
                    ProductCommission::updateOrCreate(
                        [
                            'product_id' => $productId,
                            'program_id' => $row[0],
                        ],
                        [
                            'commission_rate' => $row[1],
                            'commission_type' => $row[2] ?? 'percentage',
                            'status' => $row[3] ?? 'active',
                            'source' => ProductCommission::SOURCE_IMPORT,
                            'last_updated_at' => now(),
                        ]
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($imported + count($errors) + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} commission rates.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->back()
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to import commissions', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to import commissions: ' . $e->getMessage());
        }
    }

    // ========== PLATFORM MANAGEMENT METHODS ==========

    /**
     * Quick add program with template (Admin)
     */
    public function quickAddProgramView()
    {
        $templates = [
            'amazon' => [
                'name' => 'Amazon Associates',
                'type' => Program::TYPE_ECOMMERCE,
                'supports_sub_affiliate' => false,
                'default_commission' => ['percentage' => 2.5],
            ],
            'flipkart' => [
                'name' => 'Flipkart Affiliate',
                'type' => Program::TYPE_ECOMMERCE,
                'supports_sub_affiliate' => false,
                'default_commission' => ['percentage' => 3.0],
            ],
            'myntra' => [
                'name' => 'Myntra Affiliate',
                'type' => Program::TYPE_ECOMMERCE,
                'supports_sub_affiliate' => false,
                'default_commission' => ['percentage' => 2.0],
            ],
            'gpay' => [
                'name' => 'GPay Referral',
                'type' => Program::TYPE_FINANCE,
                'supports_sub_affiliate' => true,
                'default_commission' => ['fixed' => 50],
            ],
            'phonepe' => [
                'name' => 'PhonePe Referral',
                'type' => Program::TYPE_FINANCE,
                'supports_sub_affiliate' => true,
                'default_commission' => ['fixed' => 30],
            ],
            'upstox' => [
                'name' => 'Upstox Account Opening',
                'type' => Program::TYPE_FINANCE,
                'supports_sub_affiliate' => true,
                'default_commission' => ['fixed' => 200],
            ],
        ];

        return view('admin.programs.quick-add', compact('templates'));
    }

    /**
     * Store program from template (Admin)
     */
    public function storeProgramFromTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template' => 'required|in:amazon,flipkart,myntra,gpay,phonepe,upstox,custom',
            'name' => 'required|string|max:255',
            'merchant_name' => 'required|string|max:255',
            'merchant_url' => 'required|url|max:500',
            'logo_url' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $templates = [
                'amazon' => [
                    'type' => Program::TYPE_ECOMMERCE,
                    'supports_sub_affiliate' => false,
                    'commission_structure' => ['percentage' => 2.5],
                ],
                'flipkart' => [
                    'type' => Program::TYPE_ECOMMERCE,
                    'supports_sub_affiliate' => false,
                    'commission_structure' => ['percentage' => 3.0],
                ],
                'myntra' => [
                    'type' => Program::TYPE_ECOMMERCE,
                    'supports_sub_affiliate' => false,
                    'commission_structure' => ['percentage' => 2.0],
                ],
                'gpay' => [
                    'type' => Program::TYPE_FINANCE,
                    'supports_sub_affiliate' => true,
                    'commission_structure' => ['fixed' => 50],
                ],
                'phonepe' => [
                    'type' => Program::TYPE_FINANCE,
                    'supports_sub_affiliate' => true,
                    'commission_structure' => ['fixed' => 30],
                ],
                'upstox' => [
                    'type' => Program::TYPE_FINANCE,
                    'supports_sub_affiliate' => true,
                    'commission_structure' => ['fixed' => 200],
                ],
            ];

            $template = $templates[$request->template] ?? [
                'type' => Program::TYPE_OTHER,
                'supports_sub_affiliate' => false,
                'commission_structure' => ['percentage' => 1.0],
            ];

            $slug = \Illuminate\Support\Str::slug($request->name);

            $program = Program::create([
                'name' => $request->name,
                'slug' => $slug,
                'type' => $template['type'],
                'merchant_name' => $request->merchant_name,
                'merchant_url' => $request->merchant_url,
                'logo_url' => $request->logo_url,
                'status' => Program::STATUS_ACTIVE,
                'commission_structure' => $template['commission_structure'],
                'supports_sub_affiliate' => $template['supports_sub_affiliate'],
            ]);

            return redirect()->route('admin.programs')
                ->with('success', 'Program created successfully from template!');
        } catch (\Exception $e) {
            Log::error('Failed to create program from template', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to create program')
                ->withInput();
        }
    }

    /**
     * Import programs from CSV (Admin)
     */
    public function importPrograms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $file = $request->file('csv_file');
            $data = array_map('str_getcsv', file($file->getRealPath()));
            
            // Skip header row
            $header = array_shift($data);
            
            $imported = 0;
            $errors = [];

            foreach ($data as $row) {
                if (count($row) < 4) continue;

                try {
                    $slug = \Illuminate\Support\Str::slug($row[0]);
                    
                    Program::create([
                        'name' => $row[0],
                        'slug' => $slug,
                        'type' => $row[1] ?? Program::TYPE_OTHER,
                        'merchant_name' => $row[2],
                        'merchant_url' => $row[3],
                        'status' => Program::STATUS_ACTIVE,
                        'commission_structure' => json_decode($row[4] ?? '{"percentage": 1.0}', true),
                        'supports_sub_affiliate' => ($row[5] ?? 'false') === 'true',
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($imported + count($errors) + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} programs.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->back()
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to import programs', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to import programs: ' . $e->getMessage());
        }
    }

    // ========== WALLET/POINTS MANAGEMENT METHODS ==========

    /**
     * Display wallets/points management (Admin)
     */
    public function walletsView(Request $request)
    {
        $query = UserPoints::with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $wallets = $query->orderBy('balance', 'desc')->paginate(20);

        return view('admin.wallets.index', compact('wallets'));
    }

    /**
     * Manual points adjustment (Admin)
     */
    public function adjustPoints(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|integer',
            'type' => 'required|in:credit,debit',
            'description' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            if ($request->type === 'credit') {
                $this->pointsService->creditPoints(
                    $userId,
                    $request->points,
                    $request->description,
                    PointsTransaction::REF_ADJUSTMENT
                );
            } else {
                $this->pointsService->debitPoints(
                    $userId,
                    $request->points,
                    $request->description,
                    PointsTransaction::REF_ADJUSTMENT
                );
            }

            return redirect()->back()
                ->with('success', 'Points adjusted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to adjust points', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to adjust points: ' . $e->getMessage());
        }
    }

    // ========== CASHBACK SETTINGS METHODS ==========

    /**
     * Display cashback settings (Admin)
     */
    public function cashbackSettingsView()
    {
        $programs = Program::where('status', Program::STATUS_ACTIVE)
            ->with('cashbackSetting')
            ->get();

        return view('admin.cashback-settings.index', compact('programs'));
    }

    /**
     * Update cashback settings (Admin)
     */
    public function updateCashbackSettings(Request $request, $programId)
    {
        $validator = Validator::make($request->all(), [
            'cashback_rate' => 'required|numeric|min:0|max:100',
            'referral_rate' => 'nullable|numeric|min:0|max:100',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_cashback_amount' => 'nullable|numeric|min:0',
            'points_per_rupee' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            CashbackSetting::updateOrCreate(
                ['program_id' => $programId],
                [
                    'cashback_rate' => $request->cashback_rate,
                    'referral_rate' => $request->referral_rate ?? 0,
                    'min_purchase_amount' => $request->min_purchase_amount ?? 0,
                    'max_cashback_amount' => $request->max_cashback_amount,
                    'points_per_rupee' => $request->points_per_rupee,
                    'status' => $request->status,
                ]
            );

            return redirect()->back()
                ->with('success', 'Cashback settings updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update cashback settings', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update cashback settings')
                ->withInput();
        }
    }

    // ========== REFERRAL MANAGEMENT METHODS ==========

    /**
     * Display referrals management (Admin)
     */
    public function referralsView(Request $request)
    {
        $query = Referral::with(['referrer', 'referred', 'program']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        $referrals = $query->orderBy('created_at', 'desc')->paginate(20);

        $programs = Program::where('status', Program::STATUS_ACTIVE)->get();

        return view('admin.referrals.index', compact('referrals', 'programs'));
    }

    // ========== REDEMPTION MANAGEMENT METHODS ==========

    /**
     * Display redemptions management (Admin)
     */
    public function redemptionsView(Request $request)
    {
        $query = PointsRedemption::with(['user', 'gift']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('redemption_type', $request->type);
        }

        $redemptions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.redemptions.index', compact('redemptions'));
    }

    /**
     * Approve redemption (Admin)
     */
    public function approveRedemption(Request $request, $redemptionId)
    {
        $redemption = PointsRedemption::findOrFail($redemptionId);

        try {
            $redemption->approve($request->admin_notes);

            return redirect()->back()
                ->with('success', 'Redemption approved successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to approve redemption', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to approve redemption');
        }
    }

    /**
     * Reject redemption (Admin)
     */
    public function rejectRedemption(Request $request, $redemptionId)
    {
        $redemption = PointsRedemption::findOrFail($redemptionId);

        try {
            $redemption->reject($request->admin_notes);

            // Refund points if rejected
            if ($redemption->status === PointsRedemption::STATUS_REJECTED) {
                $this->pointsService->creditPoints(
                    $redemption->user_id,
                    $redemption->points_used,
                    "Redemption rejected - Refund",
                    PointsTransaction::REF_ADJUSTMENT,
                    $redemption->id
                );
            }

            return redirect()->back()
                ->with('success', 'Redemption rejected successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to reject redemption', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to reject redemption');
        }
    }

    /**
     * Mark redemption as completed (Admin)
     */
    public function completeRedemption($redemptionId)
    {
        $redemption = PointsRedemption::findOrFail($redemptionId);

        try {
            $redemption->markAsCompleted();

            return redirect()->back()
                ->with('success', 'Redemption marked as completed!');
        } catch (\Exception $e) {
            Log::error('Failed to complete redemption', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to complete redemption');
        }
    }
}
