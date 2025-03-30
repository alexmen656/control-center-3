module.exports = {
  publicPath: "/",
  devServer: {
    client: {
      overlay: false,
    },
    host: "0.0.0.0",
    port: 4000,
    historyApiFallback: true,
  },
};