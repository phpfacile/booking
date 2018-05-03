<?php
namespace PHPFacile\Booking\Service;

interface BookingExtraDataServiceInterface
{
    /**
     * Stores extra data along with the booking data
     *
     * @param mixed      $extraData    Additionnal data to store on booking
     * @param int|string $bookingSetId Identifier of the booking
     *
     * @return void
     */
    public function insertExtraData($extraData, $bookingSetId);

    /**
     * Updates extra data stored along with the booking data
     *
     * @param mixed      $extraData    Additionnal data to be updated for the booking
     * @param int|string $bookingSetId Identifier of the booking
     *
     * @return void
     */
    public function updateExtraData($extraData, $bookingSetId);

    /**
     * Deletes extra data stored along with the booking data
     *
     * @param int|string $bookingSetId Identifier of the booking
     *
     * @return void
     */
    public function deleteExtraData($bookingSetId);
}
