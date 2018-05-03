<?php
namespace PHPFacile\Booking\Service;

use PHPFacile\Booking\Quota\Service\NoQuotaService as DefaultQuotaService;
use PHPFacile\Booking\Quota\Service\QuotaServiceInterface;

use PHPFacile\Booking\Exception\NoMoreUnitAvailableException;

abstract class BookingService implements BookingServiceInterface
{
    /**
     * Quota service
     *
     * @var QuotaServiceInterface $quotaService
     */
    protected $quotaService;

    /**
     * Extra data service
     *
     * @var BookingExtraDataServiceInterface $bookingExtraDataService
     */
    protected $bookingExtraDataService;

    /**
     * Constructor
     *
     * @return BookingService
     */
    public function __construct()
    {
        $this->quotaService = new DefaultQuotaService();
    }

    /**
     * Defines a quota service
     *
     * @param QuotaServiceInterface $quotaService Quota service used to detect if quotas are reached
     *
     * @return void
     */
    public function setQuotaService(QuotaServiceInterface $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Defines a quota service
     *
     * @param BookingExtraDataServiceInterface $bookingExtraDataService To store extra data along with booking data
     *
     * @return void
     */
    public function setBookingExtraDataService(BookingExtraDataServiceInterface $bookingExtraDataService)
    {
        $this->bookingExtraDataService = $bookingExtraDataService;
    }

    /**
     * Returns the nb of bookings (i.e. items booked) for a given pool (i.e. pool of bookable items)
     *
     * @param object|int|string $pool   Pool object (not yet managed) or id of the pool
     * @param mixed             $filter Filter to count only items matching the filter rules
     *
     * @return integer
     */
    public function getNbBookings($pool, $filter = null)
    {
        if (true === is_object($pool)) {
            throw new \Exception('Not yet implemented');
        } else {
            $poolId = $pool;
        }

        return $this->getNbBookingsByPoolId($poolId, $filter);
    }

    /**
     * Books an item for a given user
     * FIXME Sometimes, the booking table doesn't need to store the userId
     * but the id of an object (ex: a - shopping - list) attached to the user.
     *
     * @param mixed $bookingItem      Item to be booked
     * @param mixed $user             User for who to book the item
     * @param mixed $bookingExtraData Additionnal data to store along with the booking data
     * @param mixed $context          Context that can be taken into account to check quota (ex: user profil if there is a quota for children, adults, etc.)
     *
     * @return void
     */
    public function book($bookingItem, $user, $bookingExtraData = null, $context = null)
    {
        if (true === is_object($bookingItem)) {
            throw new \Exception('Booking by pool object (not id), not yet implemented');
        } else {
            $poolId = $bookingItem;
        }

        if (true === is_object($user)) {
            throw new \Exception('Booking by user object (not id), not yet implemented');
        } else {
            $userId = $user;
        }

        return $this->bookByPoolIdAndUserId($poolId, $userId, $bookingExtraData, $context);
    }

    /**
     * Books an item for a given user using their ids
     *
     * @param integer|string $poolId           Id of the pool (i.e. kind of item) from which an item must be booked
     * @param integer|string $userId           Id of the user for who to book the item
     * @param mixed          $bookingExtraData Additionnal data to store along with the booking data
     * @param mixed          $context          Context that can be taken into account to check quota (ex: user profil if there is a quota for children, adults, etc.)
     *
     * @return void
     */
    public function bookByPoolIdAndUserId($poolId, $userId, $bookingExtraData = null, $context = null)
    {
        // FIXME Is it the right way to manage a context for quota checking ??
        if (null === $context) {
            $context = ['user_id' => $userId];
            if (null !== $bookingExtraData) {
                $context = ($context + $bookingExtraData);
            }
        }

        // If there is no quota... then there is no need for concurrency access checking
        if (true === $this->quotaService->isPoolWithQuota($poolId)) {
            // Check if quota is already reached
            // So as to avoid further useless database interactions
            if (true === $this->quotaService->isQuotaReached($poolId, $context)) {
                // If quota is already reached for sure we can't book a new unit within poolId
                throw new NoMoreUnitAvailableException('No more unit available');
            }

            /*
                // If quota is not reach... not sure we can actually book a new unit
                // Due to concurrent access managment, we can't say that we're going to
                // - count how many units are currently booked
                // - then if ok (quota not reached) actually book a new one.
                // Because, in this case, in case of concurrent access, both "thread"
                // will "say" ok it remains 1 unit. Then both "thread" will book
                // a new unit. As a consequence 2 units would be booked for only one
                // available unit.
                // Instead, we will "pre-book" a new unit, then check if it
                // is ok, regarding the stock. Thanks to the database we should be able
                // to check which thread "pre-booked" the last unit 1st. The other
                // pre-booked item will be released (and destroy)
                // Step 1:
                //   Mark a new entry in database with status
                //   self::ITEM_STATUS_ABOUT_TO_BE_BOOKED
            */

            // FIXME Make sure $bookingSetId is unique even if generated on different servers
            $bookingSetId = uniqid();

            // FIXME Allow actual booking of a set of units (not a single one)
            $this->addBookingForPoolIdByUserId($poolId, $userId, BookingServiceInterface::BOOKING_STATUS_PREBOOKED, $bookingSetId);

            /*
                // Step 2:
                //   Compare nb of itemInstance for which status
                //    self::BOOKING_STATUS_PREBOOKED
                // or self::BOOKING_STATUS_BOOKED
                // or self::BOOKING_STATUS_PREBOOKING_ABOUT_TO_BE_CANCELLED (?)
                // only consider the bookings made before the current pre-booking
                // (including this later). To get list of unit booked before,
                // use timestamp (date + time) as well as id (assuming it is sequential
                // so as to know the order for 2 reservations requests made at the same time)
            */

            if (true === $this->quotaService->isOverQuota($poolId, $bookingSetId, $context)) {
                /*
                    // FIXME Not sure whether we will have to
                    // A - Set it back to available (mainly in case of pre-created itemInstance)
                    // B - Delete it (mainly in case of itemInstance created on the fly)
                    //$this->cancelPreReservation($itemInstance);
                */

                throw new \Exception('Not yet implemented. Over Quota. (pre-reserved entry to be removed)');
            }

            // Switch from status pre-booked to booked
            $this->updateBookingStatusForBookingSetId($bookingSetId, BookingServiceInterface::BOOKING_STATUS_BOOKED);
        } else {
            // FIXME Make sure $bookingSetId is unique even if generated on different servers
            $bookingSetId = uniqid();
            $this->addBookingForPoolIdByUserIdWithNoStatus($poolId, $userId, $bookingSetId);
        }

        if (null !== $this->bookingExtraDataService) {
            $this->bookingExtraDataService->insertExtraData($bookingExtraData, $bookingSetId);
        } else if (null !== $bookingExtraData) {
            // $this->cancelPreReservation($itemInstance);
            throw new \Exception('Booking extra data provided but no bookingExtraDataService defined');
        }
    }

    /**
     * Returns the nb of bookings (i.e. items booked) for a given pool (i.e. pool of bookable items) using the pool id
     *
     * @param integer|string $poolId Id of the pool
     * @param mixed          $filter Filter to count only items matching the filter rules
     *
     * @return integer
     */
    abstract protected function getNbBookingsByPoolId($poolId, $filter = null);

    /**
     * Books (add booking data)
     *
     * @param integer|string $poolId       Id of the pool (of items from which a booking is performed)
     * @param integer|string $userId       Id of the user
     * @param string         $status       Any of the BookingServiceInterface::BOOKING_STATUS_*
     * @param integer|string $bookingSetId Id of the booking set (useful when there are several bookings at the same time for the same user)
     *
     * @return integer|string $bookingId Id of the booking (reservation)
     */
    abstract protected function addBookingForPoolIdByUserId($poolId, $userId, $status, $bookingSetId);

    /**
     * Books (add booking data) but provide no status
     *
     * @param integer|string $poolId       Id of the pool (of items from which a booking is performed)
     * @param integer|string $userId       Id of the user
     * @param integer|string $bookingSetId Id of the booking set (useful when there are several bookings at the same time for the same user)
     *
     * @return integer|string $bookingId Id of the booking (reservation)
     */
    abstract protected function addBookingForPoolIdByUserIdWithNoStatus($poolId, $userId, $bookingSetId);

    /**
     * Update the status of a booking (ex: pre-reservation -> reservation)
     *
     * @param integer|string $bookingSetId Id of the booking set (useful when there are several bookings at the same time for the same user)
     * @param string         $status       Any of the BookingServiceInterface::BOOKING_STATUS_*
     *
     * @return void
     */
    abstract protected function updateBookingStatusForBookingSetId($bookingSetId, $status);

}
