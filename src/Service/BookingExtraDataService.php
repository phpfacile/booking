<?php
namespace PHPFacile\Booking\Service;

class BookingExtraDataService implements BookingExtraDataServiceInterface
{
    /**
     * Stores extra data along with the booking data
     *
     * @param mixed      $extraData    Additionnal data to store on booking
     * @param int|string $bookingSetId Identifier of the booking
     *
     * @return void
     */
    public function insertExtraData($extraData, $bookingSetId)
    {
        throw new \Exception(__METHOD__.' not yet implemented');
    }

    /**
     * Updates extra data stored along with the booking data
     *
     * @param mixed      $extraData    Additionnal data to be updated for the booking
     * @param int|string $bookingSetId Identifier of the booking
     *
     * @return void
     */
    public function updateExtraData($extraData, $bookingSetId)
    {
        throw new \Exception(__METHOD__.' not yet implemented');
    }

    /**
     * Deletes extra data stored along with the booking data
     *
     * @param int|string $bookingSetId Identifier of the booking
     *
     * @return void
     */
    public function deleteExtraData($bookingSetId)
    {
        throw new \Exception(__METHOD__.' not yet implemented');
    }
}
