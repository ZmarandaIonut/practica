<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class ProductController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $products = Product::query();

            $perPage = $request->get('perPage', 20);
            $search = $request->get('search', '');

            if ($search && $search !== '') {
                $products = $products->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            $categoryId = $request->get('category');

            if ($categoryId) {
                $products = $products->where('category_id', $categoryId);
            }

            $status = $request->get('status');

            if ($status) {
                $products = $products->where('status', $status);
            }

            $products = $products->paginate($perPage);

            $results = [
                'data' => $products->items(),
                'currentPage' => $products->currentPage(),
                'perPage' => $products->perPage(),
                'total' => $products->total(),
                'hasMorePages' => $products->hasMorePages()
            ];

            return $this->sendResponse($results);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get($id){
        try{
            $product = Product::find($id);
            if(!$product){
                return $this->sendError(
                  "Not found",
                  [],
                  Response::HTTP_NOT_FOUND
                );
            }
            return $this->sendResponse($product->toArray());
        }
        catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request){
        try{
            $product = Product::find($id);
            if(!$product){
                return $this->sendError("Not found", [], Response::HTTP_NOT_FOUND);
            }
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'name'=> 'required|max:100|unique:products,name',
                'description' => 'required',
                'quantity' => 'required|integer|gte:0',
                'price' => 'required|numeric|gt:0',
                'status' => 'required|integer|between:0,1'
            ]);
            if($validator->fails()){
                return $this->sendError($validator->messages()->toArray());
            }

            $categoryID = $request->get("category_id");
            $category = Category::find($categoryID);
            if(!$category){
              return $this->sendError("Plase insert a valid category ID", [], Response::HTTP_NOT_FOUND);
            }
            if(count($category->childs) > 0){
                return $this->sendError("This category contains subcategories.");
            }

            $productName = $request->get("name");
            $productDescription = $request->get("description");
            $productQuantity = $request->get("quantity");
            $productPricie = $request->get("price");
            $productStatus = $request->get("status");

            $product->name = $productName;
            $product->description = $productDescription;
            $product->quantity = $productQuantity;
            $product->price = $productPricie;
            $product->status = $productStatus;

            $product->save();

            return $this->sendResponse($product->toArray());

        }
        catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id){
        try{
          $product = Product::find($id);
          if(!$product){
            return $this->sendError("Product not found", [], Response::HTTP_NOT_FOUND);
          }
          $product->delete();

          return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        }
        catch (Exception $exception){
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function add(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'name'=> 'required|max:100|unique:products,name',
                'description' => 'required',
                'quantity' => 'required|integer|gte:0',
                'price' => 'required|numeric|gt:0',
                'status' => 'required|integer|between:0,1'
            ]);
            if($validator->fails()){
                return $this->sendError($validator->messages()->toArray());
            }
            $categoryID = $request->get("category_id");
            $category = Category::find($categoryID);
            if(!$category){
                return $this->sendError("Plase insert a valid category ID", [], Response::HTTP_NOT_FOUND);
            }

            if(count($category->childs) > 0){
                return $this->sendError("This category contains subcategories.");
            }

            $productCategoryID = $request->get("category_id");
            $productName = $request->get("name");
            $productDescription = $request->get("description");
            $productQuantity = $request->get("quantity");
            $productPrice = $request->get("price");
            $productStatus = $request->get("status");
            
            $product = new Product();
            $product->category_id = $productCategoryID;
            $product->name = $productName;
            $product->description = $productDescription;
            $product->quantity = $productQuantity;
            $product->price = $productPrice;
            $product->status = $productStatus;

            $product->save();
            return $this->sendResponse([], Response::HTTP_CREATED);
        }
        catch (Exception $exception){
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


    }

    public function upload(Request $request)
    {
        if ($request->has('image')) {
            $file = $request->file('image');

            $filename = 'P'.time().'.'.$file->getClientOriginalExtension();

            $path = 'products/';

            Storage::putFileAs($path, $file, $filename);

            return $path.$filename;
        }
    }

    public function getAllProductsForCategory($categoryId)
    {
        $products = Product::where('category_id', $categoryId)
            ->orWhereHas('category', function ($query) use ($categoryId) {
               $query->where('parent_id', $categoryId)
                   ->orWhereHas('parent', function ($query) use ($categoryId) {
                       $query->where('parent_id', $categoryId);
                   });
            })->get();

//        $categories = [$categoryId];
//
//        $category = Category::find($categoryId);
//
//        if (count($category->childs) > 0) {
//            foreach ($category->childs as $subCategory) {
//                $categories[] = $subCategory->id;
//
//                if (count($subCategory->childs) > 0) {
//                    foreach ($subCategory->childs as $subSubCategory) {
//                        $categories[] = $subSubCategory->id;
//                    }
//                }
//            }
//        }
//
//        $products = Product::whereIn('category_id', $categories)->get();

        return $products->toArray();
    }
}
