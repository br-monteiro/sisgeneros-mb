<?php
namespace App\Helpers;

use HTR\System\ControllerAbstract;

class Pagination
{

    const CURRENT_PAGE = '%PAGE%';

    /**
     * Build the buttons of pagination. This method returns a string like
     * 
     * &lt;nav>
     *     &lt;ul class="pagination">
     *         &lt;li>
     *             &lt;a href="controller-name/ver/pagina/1" aria-label="Previous" class="page-link">
     *                 &lt;span aria-hidden="true">&laquo;&lt;/span>
     *             &lt;/a>
     *         &lt;/li>
     *         &lt;li class="active">
     *             &lt;a href="controller-name/ver/pagina/1"  class="page-link">1&lt;/a>
     *         &lt;/li>
     *         &lt;li class="">
     *             &lt;a href="controller-name/ver/pagina/2"  class="page-link">2&lt;/a>
     *         &lt;/li>
     *         &lt;li>
     *             &lt;a href="controller-name/ver/pagina/2" aria-label="Next" class="page-link">
     *                 &lt;span aria-hidden="true">&raquo;&lt;/span>
     *             &lt;/a>
     *         &lt;/li>
     *     &lt;/ul>
     * &lt;/nav>
     * 
     * @param ControllerAbstract $controller The instance of Controller
     * @param callable $callback The callback used to customize the address
     * @return string The HTML
     */
    public static function make(ControllerAbstract $controller, $callback = null): string
    {
        $template = "";
        $view = $controller->getView();

        if (
            isset($view->btn['link'], $view->controller) &&
            is_array($view->btn['link']) &&
            count($view->btn['link']) > 1
        ) {

            $dataPagination = self::buildDataPagination($view->btn, $view->controller, $callback);

            $template = ''
                . '<nav>'
                . '     <ul class="pagination">'
                . '         <li>'
                . "             <a href='" . ($dataPagination['previous'] ?? '') . "' aria-label='Previous' class='page-link'>"
                . '                 <span aria-hidden="true">&laquo;</span>'
                . '             </a>'
                . '         </li>';

            foreach ($view->btn['link'] as $pageNumber) {
                $active = $view->btn['current'] == $pageNumber ? 'active' : '';
                $current = '';

                if (isset($dataPagination['current'])) {
                    $current = str_replace(self::CURRENT_PAGE, $pageNumber, $dataPagination['current']);
                }

                $template .= ''
                    . "<li class='{$active}'>"
                    . "     <a href='{$current}' class='page-link'>"
                    . $pageNumber
                    . "     </a>"
                    . "</li>";
            }

            $template .= ''
                . '         <li>'
                . "             <a href='" . ($dataPagination['next'] ?? '') . "' aria-label='Next' class='page-link'>"
                . '                 <span aria-hidden="true">&raquo;</span>'
                . '             </a>'
                . '         </li>'
                . '     </ul>'
                . '</nav>';
        }

        return $template;
    }

    /**
     * This method is necessary to build the addresses buttons according the callback
     * @param array $btns The data buttons - Returned of \HTR\Helpers\Paginator\Paginator::getNaveBtn()
     * @param string $controllerName The controller name
     * @param type $callback The function runned as callback
     * @return array
     */
    private static function buildDataPagination(array $btns, string $controllerName, $callback = null): array
    {
        $dataPagination = [];

        if (is_callable($callback)) {
            $dataPagination = $callback($btns, $controllerName);

            if (!is_array($dataPagination)) {
                $dataPagination = [];
            }
        } else {
            $dataPagination = (function($btn, $controllerName) {
                    return [
                        'previous' => "{$controllerName}ver/pagina/{$btn['previous']}",
                        'next' => "{$controllerName}ver/pagina/{$btn['next']}",
                        'current' => "{$controllerName}ver/pagina/" . self::CURRENT_PAGE
                    ];
                })($btns, $controllerName);
        }

        return $dataPagination;
    }
}
