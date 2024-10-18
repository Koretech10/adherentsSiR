<?php

namespace App\Controller;

use App\Repository\MemberRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/member_card')]
class MemberCardController extends AbstractController
{
    public function __construct(private readonly MemberRepository $memberRepository)
    {
    }

    #[Route('/list', name: 'member_card_list')]
    public function list(): Response
    {
        return $this->render('member_card/list.html.twig', [
            'members' => $this->memberRepository->getUnexpiredMembers(new \DateTime()),
        ]);
    }

    #[Route(path: '/carte/model', name: 'member_card_show')]
    public function show(Request $request): Response
    {
        $id = $request->query->get('id_ad');
        $member = $this->memberRepository->find($id);

        if (null === $member) {
            throw new EntityNotFoundException(\sprintf('Member "%s" not found', $id));
        }

        return $this->render('member_card/show.html.twig', [
            'member' => $member,
            'avatar' => \sprintf('img/avatar/%s', $member->getAvatar()),
        ]);
    }
}
