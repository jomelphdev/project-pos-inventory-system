<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function saveSlug(Request $request)
    {
        $org = $request->user()->organization;

        $org->slug = $request->input('slug');

        try
        {
            $org->save();
        }
        catch (QueryException $e)
        {
            $sqlErrorCode = $e->errorInfo[1];

            if ($sqlErrorCode == 1062)
            {
                return response()->error('Someone else is already using this as their URL please choose a new one and try again.');
            }

            return response()->error('Something went wrong while trying to save URL.');
        }

        return response()->success();
    }
}
