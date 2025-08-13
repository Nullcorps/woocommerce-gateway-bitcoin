export const testConfig = {
  url: "http://localhost:8888/",
  users: {
    admin: {
      username: "admin",
      password: "password"
    },
    customer: {
      username: "customer", 
      password: "password"
    }
  },
  products: {
    simple: {
      name: "Album"
    }
  },
  addresses: {
    customer: {
      billing: {
        firstname: "John",
        lastname: "Doe",
        company: "Automattic",
        country: "US",
        addressfirstline: "addr 1", 
        addresssecondline: "addr 2",
        city: "San Francisco",
        state: "CA",
        postcode: "94107",
        phone: "123456789",
        email: "john.doe@example.com"
      }
    }
  }
};