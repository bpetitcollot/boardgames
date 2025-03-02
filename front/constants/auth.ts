import CredentialsProvider from "next-auth/providers/credentials";
import {AuthOptions, getServerSession} from "next-auth";

export const authOptions: AuthOptions = {
    providers: [
        CredentialsProvider({
            // The name to display on the sign in form (e.g. 'Sign in with...')
            name: 'Connexion',
            // The credentials is used to generate a suitable form on the sign in page.
            // You can specify whatever fields you are expecting to be submitted.
            // e.g. domain, username, password, 2FA token, etc.
            // You can pass any HTML attribute to the <input> tag through the object.
            credentials: {
                username: { label: "Email", type: "text", },
                password: { label: "Password", type: "password" }
            },
            async authorize(credentials, req) {
                // You need to provide your own logic here that takes the credentials
                // submitted and returns either a object representing a user or value
                // that is false/null if the credentials are invalid.
                // e.g. return { id: 1, name: 'J Smith', email: 'jsmith@example.com' }
                // You can also use the `req` object to obtain additional parameters
                // (i.e., the request IP address)
                const res = await fetch("http://api.boardgames.localhost/login", {
                    method: 'POST',
                    body: JSON.stringify(credentials),
                    headers: { "Content-Type": "application/json" }
                })
                const user = await res.json()

                // If no error and we have user data, return it
                if (res.ok && user) {
                    return user
                }
                // Return null if user data could not be retrieved
                return null
            }
        })
    ],
    session: {
        strategy: 'jwt',
    },
    callbacks: {
        async session({ session, token, user }) {
            session.user.username = token.username

            return session
        },
        async jwt({token, user}){
            if (user){
                token.username = user.username;
            }
            return token;
        }
    },
    events: {
        async signOut(message){
            await fetch("http://api.boardgames.localhost/logout");
        }
    },
    pages: {
        signIn: '/connexion'
    }
}

export const getSession = () => getServerSession(authOptions);