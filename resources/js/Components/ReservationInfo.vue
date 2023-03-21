<template>
    {{ reservationData }}
</template>

<script>
export default {
    name: "ReservationInfo",
    props: {
        reservationData: Object,
    },
    setup(props) {
        let reservationData = props.reservationData
        return {
            reservationData,
        }

    }
}
</script>
