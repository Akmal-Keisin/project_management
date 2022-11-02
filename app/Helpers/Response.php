<?php 

namespace App\Helpers;

class Response {

	public static function success($data, $msg) {
		$responseData = [];
		$responseData['status'] = 200;
		$responseData['info'] = $msg;
		if (!is_null($data)) {
			$responseData['data'] = $data;
		}

		return response()->json($responseData);
	}

	public static function failed($data, $msg) {
		$responseData = [];
		$responseData['status'] = 400;
		$responseData['info'] = $msg;
		if (!is_null($data)) {
			$responseData['data'] = $data;
		}

		return response()->json($responseData);
	}

	public static function error() {
		$responseData = [];
		$responseData['status'] = 500;
		$responseData['info'] = 'Internal server error';
		return response()->json($responseData);
	}
}