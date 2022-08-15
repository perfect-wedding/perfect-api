<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Market;
use App\Models\Settings;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    protected $admin_img_conf = [
        'logo',
        'testimonials',
        'home_banner',
        'auth_banner',
        'services_banner',
        'newsletter_banner',
        'who_we_are_banner',
    ];

    protected $admin_main_conf = [
        'featured_services_title',
        'featured_services_intro',
        'offering_header_title',
        'offering_header_info',
        'services_header_title',
        'services_header_info',
        'home_banner_tagline',
        'home_banner_subline',
        'testimonial_title',
        'testimonial_info',
        'who_we_are_title',
        'newsletter_title',
        'copyright_holder',
        'newsletter_info',
        'who_we_are',
        'services',
        'offerings',
    ];

    public function index(Request $request)
    {
        $app_data = [
            'page' => 'admin.index',
            'title' => 'Admin Dashboard',
            'vendor_page' => 'dashboard',
            'count_items' => Market::count(),
            'market_items' => Market::paginate(15),
            'categories' => Category::orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.shop', $app_data);
    }

    public function categories(Request $request, $action = 'list', Category $category = null)
    {
        $app_data = [
            'page' => 'admin.manage.categories',
            'title' => 'Business Manager (Categories)',
            'vendor_page' => 'categories',
            'count_categories' => Category::count(),
            'categories' => Category::orderBy('priority', 'ASC')->paginate(15),
        ];

        $view = '';

        if ($action === 'create') {
            $view = '-create';
            $app_data['item'] = $category;
            $app_data['title'] = 'Create Category';
            $app_data['vendor_page'] = 'add.categories';

            if ($request->isMethod('post')) {
                $request->validate([
                    'title' => ['required', 'string', 'min:3'],
                    'priority' => ['required', 'numeric'],
                ]);

                $new = ($category && $category->exists()) ? $category : new Category;
                $new->title = $request->title;
                $new->priority = $request->priority;
                $new->type = $request->type;
                if (! $new->slug) {
                    $new->slug = Category::whereSlug($slug = Str::of($request->title)->slug())->first() ? $slug.rand() : $slug;
                }

                $new->image = $category->image ?? '';

                if ($image = $request->file('image')) {
                    $category && Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$category->image);

                    $new->image = Str::of($image->storeAs(
                        'public/uploads',
                        $image->hashName()
                    ))->replace('public/', '');
                }

                $new->save();

                return redirect()->route('admin.manage.categories', ['create', $new->id])->with([
                    'message' => [
                        'msg' => $category ? 'Category Updated.' : 'Category Created.',
                        'type' => 'success',
                    ],
                ]);
            }
        } elseif ($action === 'delete') {
            $category && Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$category->image);
            $category->delete();

            return back()->with([
                'message' => [
                    'msg' => 'Category Deleted.',
                    'type' => 'success',
                ],
            ]);
        }

        $app_data['appData'] = collect($app_data);

        return view('admin.categories'.$view, $app_data);
    }

    public function settings(Request $request, $group = 'main')
    {
        $testimonials = Testimonial::get();
        $app_data = [
            'page' => 'admin.manage.categories',
            'title' => 'Configuration ('.ucwords($group).')',
            'vendor_page' => 'settings.'.$group,
            'group' => $group,
            'testimonials' => $testimonials->isNotEmpty() ? $testimonials : (new Testimonial)->default(),
        ];

        if ($request->isMethod('post')) {
            // Save the testimonials
            $this->saveTestimonial($request, $group);

            if ($images = $request->file()) {
                // Save all uploaded images
                $group !== 'testimonials' && collect($images)->each(function ($item, $key) {
                    if (in_array($key, $this->admin_img_conf)) {
                        if (! ($s = Settings::where('key', $key)->first())) {
                            $s = new Settings;
                            $s->key = $key;
                        }
                        if ($s->key) {
                            if (Str::contains($s->value, ['uploads', 'media'])) {
                                Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$s->value);
                            }
                            $s->value = Str::of($item->storeAs(
                                'public/uploads', $item->hashName()
                            ))->replace('public/', '');
                            $s->save();
                        }
                    }
                });
            }
            collect($request->all())->each(function ($value, $key) {
                if (in_array($key, $this->admin_main_conf) && $key !== '_token' && ! request()->hasFile($key)) {
                    $s = Settings::where('key', $key)->first();
                    if (! $s) {
                        $s = new Settings;
                        $s->key = $key;
                    }
                    $s->value = is_array($value) ? json_encode($value) : $value;
                    $s->save();
                }
            });

            return redirect()->route('admin.settings', $group)->with([
                'message' => [
                    'msg' => 'Settings Saved.',
                    'type' => 'success',
                ],
            ]);
        }

        $app_data['appData'] = collect($app_data);

        return view('admin.settings', $app_data);
    }

    public function saveTestimonial(Request $request, $group = 'testimonials')
    {
        if ($group === 'testimonials') {
            $request->validate([
                'testimonials.*.title' => ['required', 'string', 'min:3'],
                'testimonials.*.author' => ['required', 'string', 'min:3'],
                'testimonials.*.content' => ['required', 'string', 'min:10'],
            ], [], [
                'testimonials.*.title' => 'Title #:position',
                'testimonials.*.author' => 'Author #:position',
                'testimonials.*.content' => 'Content #:position',
            ]);

            $ids = collect($request->get('testimonials'))->map(fn ($v) =>$v['id'])->toArray();
            $deletables = Testimonial::whereNotIn('id', $ids)->get()->each(function ($v) {
                Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$v->photo);
                $v->delete();
            });

            foreach ($request->testimonials as $key => $test) {
                $testimonial = Testimonial::firstOrNew(['id' => $test['id']]);
                $testimonial->title = $test['title'];
                $testimonial->author = $test['author'];
                $testimonial->content = $test['content'];
                if ($photo = $request->file("testimonials.{$key}.photo")) {
                    $testimonial->photo && Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$testimonial->photo);
                    $testimonial->photo = Str::of($photo->storeAs(
                        'public/uploads', $photo->hashName()
                    ))->replace('public/', '');
                }
                $testimonial->save();
            }
        }
    }
}
