import {Box, Heading} from "@chakra-ui/react";
import SigninForm from "@/components/SigninForm";

export default function SignIn() {
    return (
        <>
            <Heading textAlign="center" mt={5}>Connexion</Heading>
            <Box maxWidth="20rem" margin="auto">
                <SigninForm />
            </Box>
        </>
    );
}
