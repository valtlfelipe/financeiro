<?php

namespace App;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
