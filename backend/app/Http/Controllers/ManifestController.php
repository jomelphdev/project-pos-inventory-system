<?php

namespace App\Http\Controllers;

use App\Events\LateReply;
use App\Http\Resources\ManifestItemResource;
use App\Jobs\UploadManifestFile;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Organization;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManifestController extends Controller
{
    /**
     * @param \App\Http\Requests\ItemStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $organization = Organization::find($request->user()->organization_id);
        
        try
        {
            DB::beginTransaction();

            $manifest = Manifest::create([
                'organization_id' => $organization->id,
                'manifest_name' => $request->input('manifest_name')
            ]);

            UploadManifestFile::dispatch($manifest, $request->file('manifest'), $request->user()->id);

            DB::commit();
        }
        catch (Exception $e)
        {
            Log::error($e);
            DB::rollBack();
            return response()->error($e->getMessage());
        }

        return response()->success([
            'success' => true,
            'message' => 'You will get a notification when we have finished processing your manifest.'
        ]);
    }

    /**
     * @param \App\Http\Requests\ItemStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryManifests(Request $request)
    {
        $manifests = Manifest::where('organization_id', $request->user()->organization_id)->get();
        return response()->success(['manifests' => $manifests]);
    }

    public function queryManifestItemsOnManifest(Request $request, $manifestId)
    {
        $query = $request->input('query', '');
        
        $manifestItems = ManifestItem::where('manifest_id', $manifestId)
            ->where(function($q) use ($query) {
                return $q
                    ->orWhere('title', 'LIKE', "%{$query}%")
                    ->orWhere('asin', 'LIKE', "%{$query}%")
                    ->orWhere('upc', 'LIKE', "%{$query}%")
                    ->orWhere('mpn', 'LIKE', "%{$query}%");
            })
            ->limit(30)
            ->get();

        return response()->success(['items' => ManifestItemResource::collection($manifestItems)]);
    }

    public function archiveManifest(Request $request, int $manifestId)
    {
        try
        {
            Manifest::find($manifestId)->delete();
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to archive manifest.');
        }

        return response()->success();
    }
}
