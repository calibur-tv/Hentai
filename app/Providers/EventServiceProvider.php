<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\User\Register' => [
            'App\Listeners\User\Register\InitUserTimeline',
            'App\Listeners\User\Register\AddDefaultTagRelation',
        ],
        'App\Events\User\UpdateProfile' => [
            'App\Listeners\User\UpdateProfile\RefreshCache',
            'App\Listeners\User\UpdateProfile\AddUserToSearch',
        ],
        'App\Events\User\DailySign' => [
            'App\Listeners\User\DailySign\UpdateUserActivity',
            'App\Listeners\User\DailySign\GiveUserCoin',
            'App\Listeners\User\DailySign\RefreshCache',
        ],
        'App\Events\Pin\Create' => [
            'App\Listeners\Pin\Create\InitPinTimeline',
            'App\Listeners\Pin\Create\RefreshUserDrafts',
            'App\Listeners\Pin\Create\UpdateAuthorTimeline',
            'App\Listeners\Pin\Create\AddPinToSearch',
            'App\Listeners\Pin\Create\AddPinTagRelation',
            'App\Listeners\Pin\Create\Trial',
        ],
        'App\Events\Pin\Update' => [
            'App\Listeners\Pin\Update\UpdatePinTimeline',
            'App\Listeners\Pin\Update\UpdateAuthorTimeline',
            'App\Listeners\Pin\Update\RefreshCache',
            'App\Listeners\Pin\Update\UpdatePinSearch',
            'App\Listeners\Pin\Update\UpdatePinTagRelation',
            'App\Listeners\Pin\Update\Trial',
        ],
        'App\Events\Pin\Move' => [
            'App\Listeners\Pin\Move\UpdatePinTimeline',
            'App\Listeners\Pin\Move\RefreshCache',
            'App\Listeners\Pin\Move\UpdatePinTagRelation',
        ],
        'App\Events\Pin\Delete' => [
            'App\Listeners\Pin\Delete\UpdatePinTimeline',
            'App\Listeners\Pin\Delete\UpdateAuthorTimeline',
            'App\Listeners\Pin\Delete\UpdateTagCounter',
            'App\Listeners\Pin\Delete\RefreshCache',
            'App\Listeners\Pin\Delete\UpdateFlowList',
            'App\Listeners\Pin\Delete\RemovePinSearch',
        ],
        'App\Events\Tag\Create' => [
            'App\Listeners\Tag\Create\InitTagMaster',
            'App\Listeners\Tag\Create\InitTagTimeline',
            'App\Listeners\Tag\Create\UpdateCreatorBookmark',
            'App\Listeners\Tag\Create\UpdateCreatorTimeline',
            'App\Listeners\Tag\Create\CreateQuestionRule',
            'App\Listeners\Tag\Create\AddTagToSearch',
            'App\Listeners\Tag\Create\RefreshParentCache',
        ],
        'App\Events\Tag\Update' => [
            'App\Listeners\Tag\Update\UpdateTagTimeline',
            'App\Listeners\Tag\Update\UpdateTagSearch',
            'App\Listeners\Tag\Update\RefreshCache',
        ],
        'App\Events\Tag\Delete' => [
            'App\Listeners\Tag\Delete\UpdateTagTimeline',
            'App\Listeners\Tag\Delete\UpdateCreatorTimeline',
            'App\Listeners\Tag\Delete\UpdateUsersBookmark',
            'App\Listeners\Tag\Delete\MoveChildrenToTrash',
            'App\Listeners\Tag\Delete\RemoveTagSearch',
            'App\Listeners\Tag\Delete\RefreshCache',
        ],
        'App\Events\User\ToggleFollowUser' => [
            'App\Listeners\User\ToggleFollowUser\UpdateRelationCounter',
            'App\Listeners\User\ToggleFollowUser\RefreshRelationCache',
        ],
        'App\Events\Comment\Create' => [
            'App\Listeners\Comment\Create\UpdatePinCounter',
            'App\Listeners\Comment\Create\UpdateCommentListCache',
            'App\Listeners\Comment\Create\UpdateFlowListCache',
        ],
        'App\Events\Comment\Delete' => [
            'App\Listeners\Comment\Delete\UpdatePinCounter',
            'App\Listeners\Comment\Delete\UpdateCommentListCache',
            'App\Listeners\Comment\Delete\RefreshCache',
        ],
        'App\Events\Comment\UpVote' => [
            'App\Listeners\Comment\UpVote\UpdateLikeCounter',
            'App\Listeners\Comment\UpVote\UpdateHottestCache'
        ],
        'App\Events\Pin\UpVote' => [
            'App\Listeners\Pin\UpVote\UpdateLikeCounter',
        ],
        'App\Events\Pin\Vote' => [
            'App\Listeners\Pin\Vote\UpdateVoteCounter',
            'App\Listeners\Pin\Vote\UpdateFlowListCache',
        ],
        'App\Events\Pin\ReVote' => [
            'App\Listeners\Pin\ReVote\UpdateVoteCounter',
        ],
        'App\Events\Pin\Reward' => [
            'App\Listeners\Pin\Reward\UpdateLikeCounter',
            'App\Listeners\Pin\Reward\UpdateFlowListCache',
        ],
        'App\Events\Tag\RemovePin' => [
            'App\Listeners\Tag\RemovePin\UpdatePinCache',
        ],
        'App\Events\Tag\AddPin' => [
            'App\Listeners\Tag\AddPin\UpdatePinCache',
        ],
        'App\Events\Message\Create' => [
            'App\Listeners\Message\Create\ClearSenderRoomCounter',
            'App\Listeners\Message\Create\UpdateMessageListCache',
            'App\Listeners\Message\Create\IncrementGetterRoomCounter',
            'App\Listeners\Message\Create\IncrementGetterUnreadMessageCount',
            'App\Listeners\Message\Create\SocketPushToGetter',
        ],
        'App\Events\User\JoinZone' => [
            'App\Listeners\User\JoinZone\UpgradeUserLevel',
            'App\Listeners\User\JoinZone\UpdateTagCounter',
            'App\Listeners\User\JoinZone\RefreshUserBookmark',
            'App\Listeners\User\JoinZone\RefreshUserTimeline',
            'App\Listeners\User\JoinZone\RefreshUserCache',
        ],
        'App\Events\Idol\BuyStock' => [
            'App\Listeners\Idol\BuyStock\UpdateIdolData',
            'App\Listeners\Idol\BuyStock\UpdateIdolPatch',
            'App\Listeners\Idol\BuyStock\UpdateIdolRankList',
            'App\Listeners\Idol\BuyStock\UpdateIdolFansList',
            'App\Listeners\Idol\BuyStock\UpdateUserData',
            'App\Listeners\Idol\BuyStock\UpdateUserIdolList',
        ],
        'App\Events\Bangumi\Pass' => [
            'App\Listeners\Bangumi\Pass\AppendBangumiUserList',
            'App\Listeners\Bangumi\Pass\AppendUserBangumiList',
            'App\Listeners\Bangumi\Pass\UpgradeUserLevel',
            'App\Listeners\Bangumi\Pass\UpdateTagCounter',
            'App\Listeners\Bangumi\Pass\RefreshUserTimeline',
            'App\Listeners\Bangumi\Pass\RefreshUserCache',
        ],
    ];
}
