<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CompanyResource;
use App\Http\Resources\v1\User\PortfolioCollection;
use App\Http\Resources\v1\User\PortfolioResource;
use App\Models\v1\Company;
use App\Models\v1\PortfolioPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company_id)
    {
        $user = Auth::user();
        $company = $user->companies()
            ->whereId($company_id)
            ->orWhere('slug', $company_id)->firstOrFail();
        $portfolios = $company->portfolios()->paginate();

        return (new PortfolioCollection($portfolios))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $company_id)
    {
        $user = Auth::user();
        $company = $user->companies()->whereId($company_id)->orWhere('slug', $company_id)->firstOrFail();

        $this->validate($request, [
            'title' => ['required', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'max:800'],
            'layout' => ['nullable', 'string', 'max:50'],
            'active' => ['nullable', 'boolean'],
            'edge' => ['nullable', 'boolean'],
            'files' => ['nullable', 'array', 'max:6'],
        ]);

        $page = [
            'user_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
            'layout' => $request->layout,
            'active' => $request->active ?? true,
            'edge' => $request->edge ?? false,
        ];

        $portfolio = $company->portfolios()->save(new PortfolioPage($page));

        return (new PortfolioResource($portfolio))->additional([
            'message' => 'You have succesfully added a new page to your portfolio.',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int|string  $company_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($company_id, $id)
    {
        $user = Auth::user();
        $company = $user->companies()->whereId($company_id)->orWhere('slug', $company_id)->firstOrFail();
        $portfolio = $company->portfolios()->whereId($id)->orWhere('slug', $id)->firstOrFail();

        return (new PortfolioResource($portfolio))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Show album resource.
     *
     * @param  \App\Models\v1\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function loadPortfolio(Company $company)
    {
        $portfolios = $company->portfolios()->get();

        return (new PortfolioCollection($portfolios))->additional([
            'company' => new CompanyResource($company),
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $company_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company_id, $id)
    {
        $user = Auth::user();
        $company = $user->companies()->whereId($company_id)->orWhere('slug', $company_id)->firstOrFail();
        $portfolio = $company->portfolios()->findOrFail($id);

        $this->validate($request, [
            'title' => ['required', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'max:800'],
            'layout' => ['nullable', 'string', 'max:50'],
            'active' => ['nullable', 'boolean'],
            'edge' => ['nullable', 'boolean'],
            'files' => ['nullable', 'array', 'max:6'],
        ]);

        $portfolio->title = $request->title;
        $portfolio->content = $request->content;
        $portfolio->layout = $request->layout;
        $portfolio->active = $request->active ?? true;
        $portfolio->edge = $request->edge ?? false;

        $portfolio->save();

        return (new PortfolioResource($portfolio))->additional([
            'message' => 'You have succesfully updated your portfolio page.',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\v1\Company  $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Company $company, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($company) {
                $item = $company->portfolios()->whereId($item)->orWhere('slug', $item)->firstOrFail();
                if ($item) {
                    $item->images()->delete();
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} portfolio pages have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = $company->portfolios()->whereId($id)->orWhere('slug', $id)->firstOrFail();
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "Portfolio page \"{$item->title}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
