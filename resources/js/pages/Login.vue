<template>
  <div class="login">
    <div class="header">
    </div>
    <div class="content">
      <form action="" name="form2">
        <mu-text-field v-model="username" label="账号" label-float icon="account_circle" full-width></mu-text-field>
        <br/>
        <mu-text-field v-model="password" type="password" label="密码" label-float icon="locked" full-width></mu-text-field>
        <br/>
        <div class="btn-radius" @click="submit">登录</div>
      </form>
      <div @click="register" class="tip-user">注册帐号</div>
    </div>
  </div>
</template>

<script type="text/ecmascript-6">
import SvgModal from "../components/svg-modal";
import Alert from "../components/Alert";
import Toast from "../components/Toast";
import ios from '../utils/ios';
import socket from '../socket';
//  import Loading from '../components/loading/loading'

export default {
  data() {
    return {
        loading: "",
        username: "",
        password: ""
    };
  },
  methods: {
    async submit() {
      const name = this.username.trim();
      const password = this.password.trim();
      if (name !== "" && password !== "") {
        const data = {
          name: name,
          password: password
        };
        const res = await this.$store.dispatch("loginSubmit", data);
        if (res.status === "success") {
          Toast({
            content: res.data.message,
            timeout: 1000,
            background: "#2196f3"
          });
          this.$store.commit("setUserInfo", {
              type: "userid",
              value: res.data.user.email
          });
          this.$store.commit("setUserInfo", {
              type: "token",
              value: res.data.user.api_token
          });
          this.$store.commit("setUserInfo", {
            type: "src",
            value: res.data.user.avatar
          });
          this.getSvgModal.$root.$options.clear();
          this.$store.commit("setSvgModal", null);
          this.$router.push({ path: "/" });
          socket.emit("login", { name });
        } else {
          Alert({
            content: res.data.message
          });
        }
        document.form2.reset();
      } else {
        Alert({
          content: "用户名和密码不能为空"
        });
      }
    },
    register() {
      this.$router.push({ path: "register" });
    }
  },
  mounted() {
    // 微信 回弹 bug
      ios();
    this.$store.commit("setTab", false);
    if (!this.getSvgModal) {
      const svg = SvgModal();
      this.$store.commit("setSvgModal", svg);
    }
    //      Loading.show()
  },
  computed: {
    getSvgModal() {
      return this.$store.state.svgmodal;
    }
  }
};
</script>

<style lang="stylus" rel="stylesheet/stylus">
.login {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background-image: url('//s3.qiufengh.com/webchat/bg.jpg');
  background-size: 100% 100%;
  background-position: center center;

  .mu-appbar {
    text-align: center;

    .mu-flat-button-label {
      font-size: 20px;
    }
  }

  .content {
    width: 80%;
    margin: 70px auto 20px;

    .mu-text-field {
      width: 100%;
    }

    .mu-raised-button {
      min-width: 80px;
      display: block;
      width: 100%;
      margin: 0 auto;
      transition: all 0.375s;

      &.loading {
        width: 80px;
        height: 80px;
        border-radius: 50%;
      }
    }
  }
}
</style>
