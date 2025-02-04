import type { Metadata } from "next";
// import "./globals.css";
import {Provider as ChakraProvider} from "@/components/ui/provider";
import Navbar from "@/components/Navbar";
import {Toaster} from "@/components/ui/toaster";
import Providers from "@/components/Providers";
import {getSession} from "@/constants/auth";


export const metadata: Metadata = {
  title: "Polpoul.com",
  description: "Jeux de société en ligne",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const session = await getSession();
  return (
    <html lang="fr" suppressHydrationWarning>
      <body>
        <ChakraProvider>
          <Providers session={session}>
            <Navbar />
              {children}
            <Toaster />
          </Providers>
        </ChakraProvider>
      </body>
    </html>
  );
}
