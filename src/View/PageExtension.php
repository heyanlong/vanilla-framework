<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午5:15
 */

namespace Vanilla\View;


class PageExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_Function('page', function ($page) {
                if (!empty($page['action']) && !empty($page['totalPages']) && !empty($page['page'])) {
                    $html[] = '<div class="paging-box cl"><div class="paging">';

                    if ($page['totalPages'] <= 5) {
                        if ($page['totalPages'] == 1) {
                            $html[] = '<strong>1</strong>';
                        }
                        if ($page['totalPages'] >= 2) {
                            if ($page['page'] != 1) {
                                $html[] = '<a href="' . $page['action'] . '/' . ($page['page'] - 1) . '">上一页</a>';
                            }
                            for ($i = 1; $i <= $page['totalPages']; $i++) {
                                if ($page['page'] == $i) {
                                    $html[] = '<strong>' . $i . '</strong>';
                                } else {
                                    $html[] = '<a href="' . $page['action'] . '/' . $i . '">' . $i . '</a>';
                                }
                            }
                            if ($page['page'] != $page['totalPages']) {
                                $html[] = '<a href="' . $page['action'] . '/' . ($page['page'] + 1) . '">下一页</a>';
                            }
                        }
                    }
                    if ($page['totalPages'] > 5) {
                        if ($page['page'] != 1) {
                            $html[] = '<a href="' . $page['action'] . '/' . ($page['page'] - 1) . '">上一页</a>';
                        }
                        if ($page['page'] != 1) {
                            $html[] = '<a href="' . $page['action'] . '/1">1</a>';
                        }
                        if ($page['page'] == 1) {
                            $html[] = '<strong>1</strong >';
                        }
                        if ($page['page'] - 2 > 2) {
                            $html[] = '<span >...</span >';
                        }

                        for ($i = $page['page'] - 2 > 1 ? $page['page'] - 2 : 2; $i <= ($page['page'] + 2 < $page['totalPages'] - 1 ? $page['page'] + 2 : $page['totalPages'] - 1); $i++) {
                            if ($page['page'] == $i) {
                                $html[] = '<strong>' . $i . '</strong>';
                            }
                            if ($page['page'] != $i) {
                                $html[] = '<a href="' . $page['action'] . '/' . $i . '">' . $i . '</a>';
                            }
                        }

                        if ($page['totalPages'] - 1 > $page['page'] + 2) {
                            $html[] = '<span >...</span >';
                        }

                        if ($page['totalPages'] == $page['page']) {
                            $html[] = '<strong>' . $page['totalPages'] . '</strong >';
                        }
                        if ($page['totalPages'] != $page['page']) {
                            $html[] = '<a href="' . $page['action'] . '/' . $page['totalPages'] . '">' . $page['totalPages'] . '</a>';
                            $html[] = '<a href="' . $page['action'] . '/' . ($page['page'] + 1) . '">下一页</a>';
                        }
                    }
                    $html[] = '</div></div>';
                    echo implode(' ', $html);
                } else {
                    echo '';
                }
            })
        ];
    }
}