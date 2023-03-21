<template>
    {{ userData }}
</template>

<script>
export default {
    name: "UserInfo",
    props: {
        userData: Object,
    },
    setup(props) {
        let userData = props.userData
        return {
            userData,
        }

    }
}
</script>
