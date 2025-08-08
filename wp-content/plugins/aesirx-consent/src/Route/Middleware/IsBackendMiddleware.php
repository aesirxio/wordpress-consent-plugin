<?php
namespace AesirxAnalytics\Route\Middleware;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;

class IsBackendMiddleware implements IMiddleware
{
  /**
   * @param Request $request
   */
  public function handle(Request $request): void
  {
    if (!current_user_can('administrator')) {
      $url = $request->getUrl()->getOriginalPath();
			if (strpos($url, 'datastream/template') === false && strpos($url, 'statement') === false) {
        throw new HttpException(esc_html__('Permission denied!', 'aesirx-consent'), 403);
			}
    }
  }
}
