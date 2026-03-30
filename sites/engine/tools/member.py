import ttyio2 as ttyio
import bbsengine4 as bbsengine

from optparse import OptionParser, OptionGroup


def command_credits(dbh, opts, username=None):
    import re

    if username is None:
        username = bbsengine.inputusername("username: ")
        if username is None:
            return None

    member = bbsengine.getmember(dbh, opts, username)
    if member is None:
        ttyio.echo(
            "command_credits.100: error loading username %r" % (username), level="error"
        )
        return None

    memberid = member["id"]

    ttyio.echo(
        "edit member credits. enter '=0' to set credits to zero, '+100' to add 100, and '-100' to subtract 100"
    )
    member = bbsengine.getmember(dbh, opts, username)
    credits = member["credits"]
    ttyio.echo("current credits: %r" % (credits))
    i = ttyio.inputstring("op", credits)
    m = re.match("([+=-])?([\d]+)", i)
    if m is None:
        ttyio.echo("invalid input %r" % (i), level="error")
        return False
    op, amount = m.groups()
    if op is None or op == "=":
        credits = amount
    elif op == "-":
        credits -= amount
    elif op == "+":
        credits += amount

    if credits < 0:
        credits = 0

    member["credits"] = credits
    res = bbsengine.update(dbh, "engine.__member", member)
    return


def main():
    parser = OptionParser(usage="usage: %prog [options] [subcommand]")
    parser.add_option(
        "--debug", default=True, action="store_true", help="run %prog in debug mode"
    )
    parser.add_option(
        "--command", default=None, action="store", help="the member command to run"
    )

    databasegroup = OptionGroup(parser, "database options")

    databasegroup.add_option(
        "--databasename",
        dest="databasename",
        action="store",
        default="zoidweb3",
        help="database name (%default)",
    )
    databasegroup.add_option(
        "--databasehost",
        dest="databasehost",
        action="store",
        default="localhost",
        help="database host (%default)",
    )
    databasegroup.add_option(
        "--databaseuser",
        dest="databaseuser",
        action="store",
        default=bbsengine.getcurrentmemberlogin(),
        help="database user (%default)",
    )
    databasegroup.add_option(
        "--databasepassword",
        dest="databasepassword",
        action="store",
        default=None,
        help="database password",
    )
    databasegroup.add_option(
        "--databaseport",
        dest="databaseport",
        action="store",
        default="5432",
        help="database port",
    )

    parser.add_option_group(databasegroup)

    (opts, args) = parser.parse_args()

    dbh = bbsengine.databaseconnect(opts)

    if opts.command == "credits":
        res = command_credits(dbh, opts)
        ttyio.echo("result of command_credits(%r): %r" % (username, res), level="debug")
    #    dbh.commit()
    dbh.close()


if __name__ == "__main__":
    main()
