<template>
    <div class="infinite-scroll" ref="container" @scroll="scroll">
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: "InfiniteScroll",
    data: () => ({
        scrollPosition: 0,
        mountedOverflow: 0,
    }),
    props: {
        loadingBreakout: { required: false, default: 50 },
    },
    methods: {
        overflow() {
            console.log("TRIGGER")
            this.$emit('overflow')
        },
        scroll() {
            const scrollPosition = this.$refs.container.scrollTop
            const scrollHeight = this.$refs.container.scrollHeight
            const clientHeight = this.$refs.container.clientHeight
            if (scrollPosition > (scrollHeight - clientHeight - this.loadingBreakout)) {
                this.overflow()
            }
        },
        scrollTrigger() {
            if (this.mountedOverflow < 30 && this.$refs.container.scrollHeight < (this.$refs.container.clientHeight + this.loadingBreakout)) {
                this.overflow() 
                this.mountedOverflow++              
                this.$nextTick(() => {
                    //this.overflow()
                    this.scrollTrigger()
                })
            }
        }
    },

    mounted() {
        this.scrollTrigger()
    }
}
</script>

<style>
.infinite-scroll {
    overflow-y: auto;
}
</style>