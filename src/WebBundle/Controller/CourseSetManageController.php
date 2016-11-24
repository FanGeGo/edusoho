<?php

namespace WebBundle\Controller;

use Topxia\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;

class CourseSetManageController extends BaseController
{
    public function indexAction(Request $request, $id)
    {
        $courseSet     = $this->getCourseSetService()->getCourseSet($id);
        $courses       = $this->getCourseService()->findCoursesByCourseSetId($id);
        $defaultCourse = $this->getCourseService()->getDefaultCourseByCourseSetId($id);

        return $this->render('WebBundle:CourseSetManage:courses.html.twig', array(
            'courseSet'     => $courseSet,
            'courses'       => $courses,
            'defaultCourse' => $defaultCourse
        ));
    }

    //基础信息
    public function baseAction(Request $request, $id)
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $this->getCourseSetService()->updateCourseSet($id, $data);
        }
        $courseSet     = $this->getCourseSetService()->getCourseSet($id);
        $defaultCourse = $this->getCourseService()->getDefaultCourseByCourseSetId($id);

        $tags = array();
        if (!empty($courseSet['tags'])) {
            $tags = $this->getTagService()->findTagsByIds(explode('|', $courseSet['tags']));
        }
        return $this->render('WebBundle:CourseSetManage:courseset-base.html.twig', array(
            'courseSet'     => $courseSet,
            'defaultCourse' => $defaultCourse,
            'tags'          => ArrayToolkit::column($tags, 'name')
        ));
    }

    public function detailAction(Request $request, $id)
    {
        $courseSet     = $this->getCourseSetService()->getCourseSet($id);
        $defaultCourse = $this->getCourseService()->getDefaultCourseByCourseSetId($id);
        return $this->render('WebBundle:CourseSetManage:courseset-detail.html.twig', array(
            'courseSet'     => $courseSet,
            'defaultCourse' => $defaultCourse
        ));
    }

    public function deleteAction(Request $request, $id)
    {
        try {
            $this->getCourseSetService()->deleteCourseSet($id, $this->getUser()->getId());
            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }

    protected function getTagService()
    {
        return ServiceKernel::instance()->createService('Taxonomy.TagService');
    }
}
