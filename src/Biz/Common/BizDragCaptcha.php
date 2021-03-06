<?php

namespace Biz\Common;

use Codeages\Biz\Framework\Context\BizAware;
use AppBundle\Common\ArrayToolkit;

class BizDragCaptcha extends BizAware
{
    const STATUS_SUCCESS = 'success';

    const STATUS_INVALID = 'invalid';

    const STATUS_EXPIRED = 'expired';

    const JIGSAW_WIDTH = 40;

    const DEVIATION = 2.5;

    const TOKENTYPE = 'drag_captcha';

    private $backgroundImages = array(
        '1.jpg',
        '2.jpg',
        '3.jpg',
        '4.jpg',
        '5.jpg',
        '6.jpg',
    );

    public function generate()
    {
        $bg = $this->backgroundImages[rand(0, 5)];
        $imagePath = $this->getImagePath($bg);
        $size = getimagesize($imagePath);

        $options = array(
            'height' => $size[1],
            'width' => $size[0],
            'bg' => $imagePath,
        );
        $options = $this->setJigsawPosition($options);
        $jigsaw = $this->getJigsaw($options);

        $token = $this->getTokenService()->makeToken(self::TOKENTYPE, array(
            'times' => 2,
            'duration' => 60 * 3,
            'userId' => 0,
            'data' => $options,
        ));

        return array(
            'token' => $token['token'],
            'jigsaw' => $jigsaw,
        );
    }

    public function getBackground($token)
    {
        $token = $this->getTokenDao()->getByToken($token);
        if (empty($token)) {
            return;
        }

        $options = $token['data'];
        $source = $this->getSource($options);
        $sub = imagecreatefrompng($this->getImagePath('jigsaw-border.png'));
        imagecopyresampled($source, $sub, $options['positionX'], $options['positionY'], 0, 0, self::JIGSAW_WIDTH, self::JIGSAW_WIDTH, 80, 80);
        ob_start();
        imagejpeg($source);

        imagedestroy($sub);
        imagedestroy($source);

        return ob_get_clean();
    }

    public function check($dragToken)
    {
        $data = $this->decodeToken($dragToken);

        if (!ArrayToolkit::requireds($data, array('token', 'captcha'), true)) {
            throw CommonException::FORBIDDEN_DRAG_CAPTCHA_REQUIRED();
        }

        $token = $this->getTokenService()->verifyToken(self::TOKENTYPE, $data['token']);

        if (empty($token)) {
            throw CommonException::FORBIDDEN_DRAG_CAPTCHA_EXPIRED();
        }

        if (!$this->validateJigsaw($token, $data['captcha'])) {
            throw CommonException::FORBIDDEN_DRAG_CAPTCHA_ERROR();
        }

        return true;
    }

    private function decodeToken($toke)
    {
        return json_decode(base64_decode(strrev($toke)), true);
    }

    private function validateJigsaw($token, $captcha)
    {
        return abs($captcha - $token['data']['positionX']) <= self::DEVIATION;
    }

    private function getSource($options)
    {
        return imagecreatefromjpeg($options['bg']);
    }

    private function getJigsaw($options)
    {
        $source = $this->getSource($options);

        $jigsawBg = imagecreatetruecolor(self::JIGSAW_WIDTH, $options['height']);
        imagesavealpha($jigsawBg, true);
        $transColour = imagecolorallocatealpha($jigsawBg, 255, 255, 255, 127);
        imagefill($jigsawBg, 0, 0, $transColour);

        imagecopymerge($jigsawBg, $source, 0, $options['positionY'], $options['positionX'], $options['positionY'], self::JIGSAW_WIDTH, self::JIGSAW_WIDTH, 100);
        ob_start();
        imagepng($jigsawBg);
        $str = ob_get_clean();
        imagedestroy($jigsawBg);
        imagedestroy($source);

        return 'data:image/png;base64,'.base64_encode($str);
    }

    private function setJigsawPosition($options)
    {
        $rate = 100;
        $options['positionX'] = rand(self::JIGSAW_WIDTH * $rate, $rate * ($options['width'] - self::JIGSAW_WIDTH)) / $rate;
        $options['positionY'] = rand(self::JIGSAW_WIDTH * $rate, $rate * ($options['height'] - self::JIGSAW_WIDTH)) / $rate;

        return $options;
    }

    private function getImagePath($name)
    {
        $rootPath = $this->biz['root_directory'];

        return $rootPath.'web/assets/img/captcha/'.$name;
    }

    /**
     * @return \Biz\User\Service\TokenService
     */
    private function getTokenService()
    {
        return $this->biz->service('User:TokenService');
    }

    private function getTokenDao()
    {
        return $this->biz->dao('User:TokenDao');
    }
}
