<template>
    <div class="hello">
        <div>
            <mu-list>
                <mu-sub-header>最近聊天记录</mu-sub-header>
                <mu-list-item avatar button :ripple="false" @click="chatwindow('1')">
                    <mu-list-item-action>
                        <mu-avatar>
                            <img :src="house1">
                        </mu-avatar>
                    </mu-list-item-action>
                    <mu-list-item-title>聊天室1</mu-list-item-title>
                    <mu-list-item-action>
                        <mu-icon value="chat_bubble"></mu-icon>
                    </mu-list-item-action>
                </mu-list-item>
                <mu-list-item avatar button :ripple="false" @click="chatwindow('2')">
                    <mu-list-item-action>
                        <mu-avatar>
                            <img :src="house2">
                        </mu-avatar>
                    </mu-list-item-action>
                    <mu-list-item-title>聊天室2</mu-list-item-title>
                    <mu-list-item-action>
                        <mu-icon value="chat_bubble"></mu-icon>
                    </mu-list-item-action>
                </mu-list-item>
            </mu-list>
            <mu-divider></mu-divider>
            <mu-list>
                <mu-sub-header>客服</mu-sub-header>
                <mu-list-item avatar button :ripple="false" @click="chatRobot()">
                    <mu-list-item-action>
                        <mu-avatar>
                            <img :src="robot">
                        </mu-avatar>
                    </mu-list-item-action>
                    <mu-list-item-title>客服大白(微信群，作者联系方式，找我)</mu-list-item-title>
                    <mu-list-item-action>
                        <mu-icon value="chat_bubble"></mu-icon>
                    </mu-list-item-action>
                </mu-list-item>
            </mu-list>
        </div>
    </div>
</template>

<script>
    import Confirm from "../components/Confirm";
    import socket from '../socket';
    import {mapState} from "vuex";
    import {ROBOT_URL, HOST_URL1, HOST_URL2} from "../const/index";

    export default {
        data() {
            return {
                house1: HOST_URL1,
                house2: HOST_URL2,
                robot: ROBOT_URL
            };
        },
        async mounted() {
            this.$store.commit("setTab", true);
            // 只全局监听一次
            if (!this.isLogin) {
                // 登录了,发送进入信息。
                if (this.userid) {
                    // 处理未读消息
                    socket.on("count", userCount => {
                        this.$store.commit("setUnread", userCount);
                        console.log(userCount);
                    });
                    this.$store.commit("setLoginState", true);
                }
            }
        },
        methods: {
            async chatwindow(roomID) {
                const uerId = this.userid;
                if (!uerId) {
                    const res = await Confirm({
                        title: "提示",
                        content: "聊天请先登录，但是你可以查看聊天记录哦~"
                    });
                    if (res === "submit") {
                        this.$router.push({path: "login"});
                    }
                    return;
                }
                this.$store.commit("setTab", false);
                this.$router.push({path: "/chat", query: {roomId: roomID}});
            },
            chatRobot() {
                this.$store.commit("setTab", false);
                this.$router.push({path: "/robot"});
            }
        },
        computed: {
            ...mapState({
                userid: state => state.userInfo.userid,
                src: state => state.userInfo.src,
                isLogin: state => state.isLogin,
                unRead1: state => state.unRead.room1,
                unRead2: state => state.unRead.room2
            })
        }
    };
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style lang="stylus" rel="stylesheet/stylus">
    .avatar {
        position: relative;

        .tip {
            position: absolute;
            right: -5px;
            top: -8px;
            padding: 0px 5px;
            border-radius: 10px;
            line-height: 20px;
            text-align: center;
            background: #ff2a2a;
            color: #fff;
            font-size: 12px;
        }

        .mu-avatar {
            img {
                border-radius: 5px;
            }
        }
    }
</style>
