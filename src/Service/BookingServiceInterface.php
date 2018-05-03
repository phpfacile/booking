<?php
namespace PHPFacile\Booking\Service;

interface BookingServiceInterface
{
    const BOOKING_STATUS_PREBOOKED = 'prebooked';
    const BOOKING_STATUS_BOOKED    = 'booked';
    const BOOKING_STATUS_PREBOOKING_ABOUT_TO_BE_CANCELLED = 'tobecancelled';
}
